<?php

namespace app\admin\service;

use app\admin\model\Room;
use app\job\GameDataJob;
use think\Db;
use think\Queue;

class GameService
{

    /**
     * 验证地址
     */
    const VERIFY_URL = 'https://api.wwapi.vip/web-api/auth/session/v2/verifyOperatorPlayerSession?traceId=FYRYLY20';

    /**
     * 游戏地址
     */
    const SPIN_URL = 'https://api.wwapi.vip/%sv2/%s?traceId=EEBTBG20';

    /**
     * 队列数量
     */
    const QUEUE_NUMS = 5;


    /**
     * 登录验证
     */
    public function verify($data)
    {
        $body = [
            'cp' => $data['op'],
            'btt' => $data['btt'],
            'vc' => 2, //TODO::未确认参数
            'pf' => 1,  //TODO::未确认参数
            'l' => $data['l'],
            'gi' => $data['game_code'],
            'os' => $data['ops'],
            'otk' => $data['ot'],
        ];

        $result = wget(self::VERIFY_URL, http_build_query($body));
        return $result;
    }

    /**
     * 队列跑数据
     */
    public function runQueue($gameCode, $tk, $path, $querys)
    {
        $config = Db::table('game_room_base_config')->where('room_id', (int)$gameCode)->find();
        $betAmounts = explode(',', $config['bet_amount']);
        $betRates = explode(',', $config['bet_rate']);

        $body = [
            'id' => strval(microtime(true) * 10000) . strval(mt_rand(10000, 99999)),
            'cs' => $betAmounts[0], //押注档位(只需最小档位)
            'ml' => $betRates[0], //押注倍率（只需最小倍率）
            'wk' => '0_C',
            'btt' => '1',
            'atk' => $tk,
            'pf' => '1',
        ];

        if ($querys['game_code'] == 63) {
            $body['gpt'] = 3;
        } elseif ($querys['game_code'] == 1695365) {
            $body['fb'] = false;
        }

        $url = sprintf(self::SPIN_URL, $path, 'spin');

        //验证接口是否存在(服务端spin和Spin接口不同)
        $func = function ($url) {
            $test = wget($url, ['t' => 'test']);
            if (is_string($test)) {
                preg_match('/\d+/', $test, $matches);
                if ($matches[0] == 404) {
                    return false;
                } else {
                    throw new \Exception('游戏接口地址异常:' . $test . ' 地址：' . $url);
                }
            }
            return true;
        };

        $check = $func($url);
        if ($check === false) {
            $url = sprintf(self::SPIN_URL, $path, 'Spin');
            $check = $func($url);
            if ($check === false) {
                throw new \Exception('游戏接口地址不存在');
            }
        }

        $lastId = Db::table('game_run_stat')->insertGetId(
            [
                'user_id' => $querys['user_id'],
                'atk' => $tk,
                'body' => json_encode($body),
                'game_code' => $gameCode,
                'nums' => $querys['nums'],
                'rtp' => $querys['rtp'] ?? 0,
                'type_id' => $querys['type_id'] ?? 0,
                'c_time' => time()
            ]
        );

        $queueNums = self::QUEUE_NUMS;
        $total = $querys['nums'];
        $runPerBatch = intval($total / $queueNums); //每批数量
        $remain = $total % $queueNums;

        for ($batch = 1; $batch <= $queueNums; $batch++) {
            //最后一批数据单独处理
            $end = 0;
            if ($batch == $queueNums) {
                $end = $runPerBatch + $remain;
            }

            $data = [
                'batch' => $batch,
                'per' => $runPerBatch,
                'url' => $url,
                'body' => $body,
                'end' => $end,
                'log_id' => $lastId
            ];

            $isPushed = Queue::push(GameDataJob::class, $data, 'game_data_batch_' . $batch);
        }
        return $lastId;
    }

    /**
     * 结果列表
     *
     * @param [type] $where
     * @param [type] $sort
     * @param [type] $order
     * @param [type] $offset
     * @param [type] $limit
     * @return array
     */
    public function getRunResultList($where, $sort, $order, $offset, $limit)
    {
        $games = Room::where('status', 1)->column('name', 'code');
        $gameTypes = config('constant.game_types');
        $list = Db::table('game_run_stat')->where($where)->where('status', 1)->order($sort, $order)->paginate($limit)->each(function ($val, $key) use ($games, $gameTypes) {
            // $val['game_name'] = $games[$val['game_code']] ?? '未知';
            $val['game_code'] = $games[$val['game_code']] ?? $val['game_code'];
            $val['type_id'] = $gameTypes[$val['type_id']] ?? $val['type_id'];
            $temp = json_decode($val['result'], true);
            $val['lose'] = $temp['0'] ?? 0;
            $win = array_sum(array_values($temp)) - $val['lose'];
            $val['win'] = sprintf('%s 【%.2f%%】', $win, $val['sus'] == 0 ? 0 : ($win / $val['sus'] * 100));
            foreach ($temp as $key => $value) {
                $val[$key] = sprintf('%s 【%.2f%%】', $value, $val['sus'] == 0 ? 0 : ($value / $val['sus'] * 100));
            }
            // $val['c_time'] = date('Y-m-d H:i:s', $val['c_time']);
            return $val;
        });

        $result = array("total" => $list->total(), "rows" => $list->items());
        return $result;
    }

    /**
     * 返回表格结果
     *
     * @param [int] $id
     * @return void
     */
    public function getRunResult($id)
    {
        $log = Db::table('game_run_stat')->where('id', $id)->find();
        if (!$log) {
            throw new \Exception('记录不存在');
        }
        if ($log['status'] == 0) {
            throw new \Exception('数据还在处理中，请稍后再试');
        }

        $result = [];
        $temp = json_decode($log['result'], true);
        if (empty($temp)) throw new \Exception('结果数据异常');
        $total = array_sum(array_values($temp));
        foreach ($temp as $k => $val) {
            $t = [
                'rate' => $k,
                'nums' => $val,
                'percent' => sprintf('%.2f%%', $total == 0 ? 0 : ($val / $total * 100))
            ];
            array_push($result, $t);
        }
        $games = Room::where('status', 1)->column('name', 'code');
        $log['game_name'] = $games[$log['game_code']] ?? '未知';
        $gameTypes = config('constant.game_types');
        $log['type_id'] = $gameTypes[$log['type_id']] ?? $log['type_id'];
        $log['result'] = $result;
        $log['lose'] = $temp['0'] ?? 0;
        $log['win'] = $total - $log['lose'];
        return $log;
    }
}
