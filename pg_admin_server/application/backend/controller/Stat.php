<?php

namespace app\backend\controller;

use app\admin\service\GameFactory;
use app\job\TestJob;
use think\Cache;
use think\Console;
use think\Db;
use think\Queue;

class Stat
{
    public function __construct()
    {
        error_reporting(E_ALL & ~E_NOTICE);
    }

    // 跑数据
    public function runData()
    {
        $logs = Db::table('game_run_stat')->where('fix_time', 0)->where('run_status', 0)->select();
        if (!$logs) return;

        $redis = Cache::store('redis')->handler();
        foreach ($logs as $log) {

            if (!$this->checkModifyStatus($log)) continue;

            $cacheKey = sprintf('log:%s:rounds:*', $log['id']);
            $result = [];
            $iterator = null;
            $batchSize = 500;
            $sus = 0;
            $totalAw = 0;
            $totalTbb = 0;
            do {
                $hashKeys = $redis->scan($iterator, $cacheKey, $batchSize);
                if (!$hashKeys) continue;
                $sus += count($hashKeys);

                $pipeline = $redis->pipeline();
                foreach ($hashKeys as $hashKey) {
                    $pipeline->hGetAll($hashKey);
                }
                $ret = $pipeline->exec();
                $result = $this->dataFormat($result, $ret);

                foreach ($ret as $val) {
                    $totalAw += $val['aw'] * 1000;
                    $totalTbb += $val['tbb'] * 1000;
                }

            } while ($iterator > 0);
            
            $redis->del($redis->keys($cacheKey));
            
            if ($log['status'] > 0) { // 报错了，数据继续处理，状态不变
                $status = $log['status'];
            } else {
                $status = empty($result) ? 2 : 1;
            }
            $errMsg = $log['err_msg'] ?? '';
            if ($status == 1) {
                $errMsg = '';
            }

            $realRtp = $totalTbb > 0 ? round($totalAw / $totalTbb, 4) * 100 : 0;
            Db::table('game_run_stat')->where('id', $log['id'])->update(['status' => $status, 'run_status' => 1, 'sus' => $sus, 'total_aw' => $totalAw, 'total_tbb' => $totalTbb, 'real_rtp' => $realRtp, 'u_time' => time(), 'err_msg' => $errMsg, 'result' => json_encode($result)]);
        }
    }

    // 修复数据
    public function fixData()
    {
        $logs = Db::table('game_run_stat')->where('fix_time', '>', 0)->where('run_status', 0)->select(); // 修复状态
        if (!$logs) return;

        $redis = Cache::store('redis')->handler();
        foreach ($logs as $log) {

            if (!$this->checkModifyStatus($log)) continue;

            $cacheKey = sprintf('log:%s:rounds:*', $log['id']);
            $result = [];
            $iterator = null;
            $batchSize = 500;
            $sus = $log['sus'] ?? 0;
            $totalAw = $log['total_aw'] ?? 0;
            $totalTbb = $log['total_tbb'] ?? 0;
            do {
                $hashKeys = $redis->scan($iterator, $cacheKey, $batchSize);
                if (!$hashKeys) continue;
                $sus += count($hashKeys);

                $pipeline = $redis->pipeline();
                foreach ($hashKeys as $hashKey) {
                    $pipeline->hGetAll($hashKey);
                }
                $ret = $pipeline->exec();
                $result = $this->dataFormat($result, $ret);

                foreach ($ret as $val) {
                    $totalAw += $val['aw'] * 1000;
                    $totalTbb += $val['tbb'] * 1000;
                }
            } while ($iterator > 0);
            
            $redis->del($redis->keys($cacheKey));

            if ($log['status'] != 4) { // 报错了，数据继续处理，状态不变
                $status = $log['status'];
            } else {
                $status = empty($result) ? 2 : 1;
            }   
            $realRtp = $totalTbb > 0 ? round($totalAw / $totalTbb, 4) * 100 : 0;

            $resultOld = json_decode($log['result'], true) ?? [];
            // 合并$result和$resultOld的key，去重后相加
            $mergedKeys = array_unique(array_merge(array_keys($result), array_keys($resultOld)));
            $mergedResult = [];
            foreach ($mergedKeys as $key) {
                $mergedResult[$key] = ($result[$key] ?? 0) + ($resultOld[$key] ?? 0);
            }
            $result = $mergedResult;

            $errMsg = $log['err_msg'] ?? '';
            if ($status == 1) {
                $errMsg = '';
            }

            Db::table('game_run_stat')->where('id', $log['id'])->update(['status' => $status, 'run_status' => 1, 'sus' => $sus, 'total_aw' => $totalAw, 'total_tbb' => $totalTbb, 'real_rtp' => $realRtp, 'u_time' => time(), 'err_msg' => $errMsg, 'result' => json_encode($result)]); 
        }
    }

    public function checkModifyStatus($log)
    {
        $flag = true;
        if (in_array($log['type_id'], [GameFactory::GAME_TYPE_WG, GameFactory::GAME_TYPE_CP, GameFactory::GAME_TYPE_PP, GameFactory::GAME_TYPE_UU])) { //wg、cp、pp、uu单线程跑
            if ($log['queue_1'] != 1) $flag = false;
        } else {
            $queueTotal = $log['queue_1'] + $log['queue_2'] + $log['queue_3'] + $log['queue_4'] + $log['queue_5'];
            if ($queueTotal < 5) $flag = false;
        }

        $diffTime = time() - $log['c_time'];
        //判断是否超时(超过20分钟)
        if ($flag === false && $diffTime > 1000) {
            Db::table('game_run_stat')->where('id', $log['id'])->update(['status' => 3]);
        }
        return $flag;
    }

    protected function dataFormat($result, $ret)
    {
        $fields = [
            [0, 1],
            [1, 2],
            [2, 3],
            [3, 4],
            [4, 5],
            [5, 10],
            [10, 15],
            [15, 20],
            [20, 25],
            [25, 30],
            [30, 40],
            [40, 50],
            [50, 75],
            [75, 100]
        ];

        if (empty($result)) {
            $result = ['0' => 0];
            foreach ($fields as $field) {
                $result[sprintf('%s~%s', $field[0], $field[1])] = 0;
            }
            $result['>100'] = 0;
        }

        foreach ($ret as $val) {
            $tbb = floatval($val['tbb'] ?? 0);
            $rate = $tbb > 0 ? ($val['aw'] / $tbb) : 0;
            if ($rate == 0) {
                $result['0'] += 1;
            } elseif ($rate > 100) {
                $result['>100'] += 1;
            } else {
                foreach ($fields as $field) {
                    if ($rate > $field[0] && $rate <= $field[1]) {
                        $result[sprintf('%s~%s', $field[0], $field[1])] += 1;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    public function testHinc()
    {
        /* $redis = Cache::store('redis')->handler();
        $pipeline = $redis->pipeline();
        $key = 'game:user:1001';

        $pipeline->hMSet($key, ['gold' => 1, 'aa' => 'fsdfdf']);

        $pipeline->hIncrBy($key, 'gold', 100);
        $pipeline->expire($key, 30);
        $pipeline->exec(); */
    }

    public function testCommand()
    {
        /* $out = Console::call('action', ['backend/stat/testHinc']);
        $result = $out->fetch();
        var_dump($result); */
        // save_log('test_command_log', json_encode(['time' => time()]));
        // Queue::push(TestJob::class, ['num' => 12], 'test');
        /* $redis = Cache::store('redis')->handler();

        $pattern = 'queues:game_data_batch*';
        $iterator = null;
        $keys = [];

        do {
            $result = $redis->scan($iterator, $pattern, 100);
            if ($result) {
                $keys = array_merge($keys, $result);
            }
        } while ($iterator > 0);
        var_dump($keys); */

    

        $gameUrl = 'https://m.wwapi.vip/2000006/index.html?otk=457-Wo_nECBgX39DERbZJ_lk4fsrdJICdpXu&l=en';
        $ret = wget($gameUrl, [], [], true);
        var_dump($ret);
    }
}
