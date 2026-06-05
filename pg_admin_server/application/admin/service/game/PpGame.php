<?php

namespace app\admin\service\game;

use app\job\GameDataJob;
use think\Db;
use think\Queue;

/**
 * PP 游戏
 */
class PpGame extends BaseGame
{
    protected $gameType = 'pp';

    protected $verifyUri = '';

    protected $gameUri = '%s/gs2c/ge/v4/gameService';

    protected $gameInit = '';
    

    public function verify($data)
    {
        // 无验证
        return ['', ''];
    }

    public function getGameInitUrl()
    {
        return sprintf($this->gameUri, $this->gameDomain);
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

        //特殊处理
        if ($gameCode == 3001001) {
            $querys['symbol'] .= 'ly';
        }

        //游戏初始化参数
        $initUrl = $this->getGameInitUrl();
        $body = [
            'action' => 'doInit',
            'symbol' => $querys['symbol'],
            'cver' => rand(100000, 999999),
            'index' => 1,
            'counter' => 1,
            'repeat' => 0,
            'mgckey' => $querys['mgckey']
        ];
        $initInfo = wget($initUrl, http_build_query($body));
        /* dump($body);
        dump($initInfo);exit; */
        if (!$initInfo) {
            throw new \Exception('获取游戏初始化信息失败');
        }

        if (is_array($initInfo) && isset($initInfo['description']) && isset($initInfo['error']) && $initInfo['error'] == 1) {
            throw new \Exception('获取游戏初始化信息失败，' . $initInfo['description']);
        }

        $result = [];
        parse_str($initInfo, $result);
        
        if (empty($result)) {
            throw new \Exception('游戏初始化信息解析失败');
        }

        if (empty($result['balance'])) {
            throw new \Exception('游戏初始化信息解析失败，余额为空');
        }

        if (empty($result['c'])) {
            throw new \Exception('游戏初始化信息解析失败，下注配置为空');
        }

        if (empty($result['l'])) {
            throw new \Exception('游戏初始化信息解析失败，线条为空');
        }


        //游戏参数
        $bl = $result['balance'];
        $body = [
            'gameCode' => $gameCode, //游戏区分（请求时删除）
            'action' => 'doSpin',
            'symbol' => $querys['symbol'],
            'c' => $result['c'],
            'l' => $result['l'],
            'sInfo' => 'n',
            'index' => 2,
            'counter' => 2,
            'repeat' => 0,
            'mgckey' => $querys['mgckey'],
            'bl' => $bl //当前余额，需要传递
        ];

        //验证游戏接口是否存在(服务端spin和Spin接口不同)
        $url = $this->checkSpinApi();

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

    public function checkSpinApi($path = '')
    {
        $path = $this->getGameInitUrl();
        //验证接口是否存在(服务端spin和Spin接口不同)
        $checkUrl = function ($url) {
            $test = wget($url, http_build_query(['t' => 'test']));
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

        $check = $checkUrl($path);
        if ($check === false) {
            throw new \Exception('游戏接口地址不存在 404');
        }
        return $path;
    }
}
