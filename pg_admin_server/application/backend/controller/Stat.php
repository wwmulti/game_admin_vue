<?php

namespace app\backend\controller;

use think\Cache;
use think\Console;
use think\Db;

class Stat
{
    public function __construct()
    {
        error_reporting(E_ALL & ~E_NOTICE);
    }

    public function runData()
    {
        $logs = Db::table('game_run_stat')->where('status', 0)->select();
        if (!$logs) return;

        $redis = Cache::store('redis')->handler();
        foreach ($logs as $log) {
            $queueTotal = $log['queue_1'] + $log['queue_2'] + $log['queue_3'] + $log['queue_4'] + $log['queue_5'];
            if ($queueTotal < 5) continue;

            $cacheKey = sprintf('log:%s:psid:*', $log['id']);

            $result = [];
            $iterator = null;
            $batchSize = 500;
            $sus = 0;
            do {
                $hashKeys = $redis->scan($iterator, $cacheKey, $batchSize);
                if (!$hashKeys) continue;
                $sus += count($hashKeys);

                $pipeline = $redis->pipeline();
                foreach ($hashKeys as $hashKey) {
                    $pipeline->get($hashKey);
                }
                $ret = $pipeline->exec();
                $result = $this->dataFormat($result, $ret);
            } while ($iterator > 0);

            Db::table('game_run_stat')->where('id', $log['id'])->update(['status' => 1, 'sus' => $sus, 'result' => json_encode($result)]);
        }
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
            // $rate = $val['tbb'] == 0 ? 0 : $val['aw'] / $val['tbb'];
            $rate = $val;
            if ($rate == 0) {
                $result['0'] += 1;
            } elseif ($rate >= 100) {
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
    }
}
