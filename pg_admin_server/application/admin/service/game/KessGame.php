<?php

namespace app\admin\service\game;

use app\admin\model\Room;
use app\job\CheckGameJob;
use app\job\GameDataJob;
use think\Cache;
use think\Db;
use think\Queue;

/**
 * Kess 游戏
 */
class KessGame extends BaseGame
{
    protected $gameType = 'kess';

    //https://api.wwapi.vip/platformapi/login/guestLogin
    protected $verifyUri = '%s/platformapi/login/guestLogin';

    protected $gameUri = '';

    protected $gameInit = '';

    //获取游戏配置地址
    protected $configUrls = [
        3000004 => '%s/slotone/fruits/configBetNums',
        3000013 => '%s/premierleague/premier/league/configBetNums',
        3000014 => '%s/ultrawheel/ultra/wheel/configBetNums',
        3000025 => '%s/orchardspin/orchard/spin/gameConfigs',
        3000026 => '%s/diamondrush/diamond/rush/gameConfigs',
        3000028 => '%s/vegascash/vegas/cash/gameConfigs',
        3000029 => '%s/kessplinko/kess/plinko/gameConfigs',
    ];

    //获取游戏接口地址
    protected $gameUrls = [
        3000004 => '%s/slotone/fruits/bet',
        3000013 => '%s/premierleague/premier/league/bet',
        3000014 => '%s/ultrawheel/ultra/wheel/bet',
        3000025 => '%s/orchardspin/orchard/spin/bet',
        3000026 => '%s/diamondrush/diamond/rush/bet',
        3000028 => '%s/vegascash/vegas/cash/bet',
        3000029 => '%s/kessplinko/kess/plinko/bet',
    ];

    public function getGameInit($gameCode)
    {
        return sprintf($this->configUrls[$gameCode], $this->gameDomain);
    }

    public function getGameUri($gameCode)
    {
        return sprintf($this->gameUrls[$gameCode], $this->gameDomain);
    }

    public function verify($data)
    {
        $body = ['1' => '1'];

        $url = sprintf($this->verifyUri, $this->gameDomain);
        $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . ($data['accessToken'] ?? ''), 'Channelid: ' . $data['channel']]]);
        if (!$result || empty($result['data']['accessToken'])) {
            throw new \Exception('登录验证失败');
        }
        return [$result['data']['accessToken'], ''];
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

        //游戏初始化参数（暂时写死不用）
        /* $initUrl = $this->getGameInit($gameCode);
        $body = [];
        $initInfo = wget($initUrl, $body, [CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . ($querys['accessToken'] ?? ''), 'Channelid: ' . $querys['channel']]]);
        if (!$initInfo || empty($initInfo['data'])) {
            throw new \Exception('获取游戏配置初始化信息失败');
        }
        if (in_array($gameCode, [3000025, 3000026, 3000028, 3000029])) {
            $config = $initInfo['quotaList'] ?? [];
        } else {
            $config = $initInfo['data'] ?? [];
        } */

        //游戏参数
        $body = [
            'gameCode' => $gameCode, //不同游戏判断（请求时删除）
            'accessToken' => $querys['accessToken'] ?? '', //构建请求头（请求时删除）
            'channel' => $querys['channel'] ?? '', //构建请求头（请求时删除）
            'param' => null
        ];

        switch ($gameCode) {
            case 3000004:      
            case 3000014:
                $body['betAmount'] = 1;
                $body['bets'] = 1; //记录当局押注（请求时删除）
                break;

            case 3000013:               
            case 3000025:           
            case 3000026:               
                $body['betAmount'] = 0.05;
                $body['line'] = 20;
                $body['bets'] = 1; //记录当局押注（请求时删除）
                break;

            case 3000028:
                $body['betAmount'] = 0.2;
                $body['line'] = 5;
                $body['bets'] = 1; //记录当局押注（请求时删除）
                break;

            case 3000029:
                $body['betAmount'] = 1;
                $body['level'] = 1;
                $body['row'] = 8;
                $body['bets'] = 1; //记录当局押注（请求时删除）
                break;
        }

        //验证游戏接口是否存在
        $url = $this->checkSpinApi($this->getGameUri($gameCode), $querys);
        
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

    public function checkSpinApi($url, $querys)
    {
        //验证接口是否存在(服务端spin和Spin接口不同)
        $checkUrl = function ($url) use ($querys) {
            $test = wget($url, ['t' => 'test'], [CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . ($querys['accessToken'] ?? ''), 'Channelid: ' . $querys['channel']]]);
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
        $check = $checkUrl($url);
        if ($check === false) {
            throw new \Exception('游戏接口地址不存在 404');
        }
        return $url;
    }
}
