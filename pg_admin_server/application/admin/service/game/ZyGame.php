<?php

namespace app\admin\service\game;

use app\job\GameDataJob;
use think\Db;
use think\Queue;

/**
 * zy 游戏
 */
class ZyGame extends BaseGame
{
    protected $gameType = 'zy';

    //https://api.wwapi.vip/web-api/auth/session/v3/verifySession
    protected $verifyUri = '%s/web-api/auth/session/v3/verifySession';

    //https://api.wwapi.vip/game-api/tower/v2/Spin
    protected $gameUri = '%s%sv2/%s';

    //https://api.wwapi.vip/game-api/tower/v2/GameInfo/Get
    protected $gameInit = '%s%sv2/GameInfo/Get';

    public function verify($data)
    {
        $body = [
            'gi' => $data['game_code'],
            'otk' => $data['otk'],
            'l' => $data['l']
        ];

        $url = sprintf($this->verifyUri, $this->gameDomain);
        $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
        if (!$result || empty($result['dt']['atk'])) {
            throw new \Exception('登录验证失败');
        }
        return [$result['dt']['atk'], $result['dt']['gdr']];
    }

    public function submitGameData($data)
    {
        $gameCode = $data['game_code'];
        $tk = $data['tk'];
        $path = $data['path'];
        $querys = $data['querys'];
        // 用于修复数据用
        $saveData = $querys['save_data'] ?? [];
        $id = $querys['id'] ?? 0;

        //游戏初始化参数
        $initUrl = sprintf($this->gameInit, $this->gameDomain, $path);
        $body = [
            'atk' => $tk
        ];
        $initInfo = wget($initUrl, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
        if (!$initInfo || !empty($initInfo['err'])) {
            throw new \Exception('获取游戏初始化信息失败');
        }

        //游戏参数
        $bl = $initInfo['dt']['bl'];
        $body = [
            'atk' => $tk,
            'bl' => $bl //当前余额，需要传递
        ];

        $multList = $initInfo['dt']['multList'] ?? [];
        $totalList = $initInfo['dt']['totalList'] ?? [];

        switch ($gameCode) {
            case 2000004:
                $body['idx'] = mt_rand(0, 4);
                $body['bet'] = 1;
                break;
            
            default:
                $body['bet'] = $totalList[0] / 1000;
                $body['mult'] = $multList[0] / 10000;
                break;
        }

        //验证游戏接口是否存在(服务端spin和Spin接口不同)
        $url = $this->checkSpinApi($path);
        
        $fix = 0;
        if ($id) {
            $fix = 1;
            $log = Db::table('game_run_stat')->where('id', $id)->find();
            if (!$log) {
                throw new \Exception('游戏记录不存在');
            }
            $lastId = $log['id'];

            // 更新游戏记录
            Db::table('game_run_stat')->where('id', $id)->update([
                'fix_time' => time(),
                'status' => 4,
                'run_status' => 0,
                'queue_1' => 0,
                'queue_2' => 0,
                'queue_3' => 0,
                'queue_4' => 0,
                'queue_5' => 0
            ]);
        } else {
            $lastId = Db::table('game_run_stat')->insertGetId(
                [
                    'user_id' => $querys['user_id'],
                    'atk' => $tk,
                    'body' => json_encode($body),
                    'game_code' => $gameCode,
                    'nums' => $querys['nums'],
                    'rounds' => $querys['rounds'],
                    'rtp' => $querys['rtp'] ?? 0,
                    'type_id' => $querys['type_id'] ?? 0,
                    'c_time' => time(),
                    'save_data' => json_encode($saveData)
                ]
            );
        }

        //提交队列
        $this->submitGameDataToQueue($lastId, $url, $body, $querys['rounds'], $querys['nums'], $fix);

        return $lastId;
    }

    //需记录每次余额只能单个队列跑
    public function submitGameDataToQueue($logId, $url, $body, $rounds, $nums, $fix = 0)
    {
        $batch = 1;
        $end = $rounds;
        $data = [
            'batch' => $batch,  //队列批次
            'per' => $rounds, //每批次需要的轮次
            'nums' => $nums, //每轮次跑的数量
            'url' => $url,
            'body' => $body,
            'end' => $end,
            'log_id' => $logId,
            'type' => $this->gameType,
            'fix' => $fix
        ];

        Queue::push(GameDataJob::class, $data, 'game_data_batch_' . $batch);
        return;
    }

    public function checkSpinApi($path)
    {
        //验证接口是否存在(服务端spin和Spin接口不同)
        $checkUrl = function ($url) {
            $test = wget($url, ['t' => 'test'], [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
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

        //拼接&检测接口正确性
        foreach ($this->gameSpins as $spin) {
            $url = sprintf($this->gameUri, $this->gameDomain, $path, $spin);
            $check = $checkUrl($url);
            if ($check === true) break;
        }
        if ($check === false) {
            throw new \Exception('游戏接口地址不存在 404');
        }
        return $url;
    }
}
