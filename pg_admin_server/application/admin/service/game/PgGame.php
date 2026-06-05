<?php

namespace app\admin\service\game;

use app\admin\model\Room;
use app\job\CheckGameJob;
use app\job\GameDataJob;
use think\Cache;
use think\Db;
use think\Queue;

/**
 * PG 游戏
 */
class PgGame extends BaseGame
{
    // 特殊处理的游戏代码
    const GAME_CODE_EX = [1980, 1990, 1995, 1996, 1997];

    protected $gameType = 'pg';

    //https://api.wwapi.vip/web-api/auth/session/v2/verifyOperatorPlayerSession?traceId=LCEFEF03
    protected $verifyUri = '%s/web-api/auth/session/v2/verifyOperatorPlayerSession?traceId=FYRYLY20';

    //https://api.wwapi.vip/game-api/mahjong-ways2/v2/Spin?traceId=WZJVAP03
    protected $gameUri = '%s/%sv2/%s?traceId=EEBTBG20';

    /**
     * 登录验证
     */
    public function verify($data)
    {
        if (in_array($data['game_code'], self::GAME_CODE_EX)) {
            return $this->verifyEx($data);
        }

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

        $url = sprintf($this->verifyUri, $this->gameDomain);
        $result = wget($url, http_build_query($body));

        if (!$result || empty($result['dt']['tk'])) {
            throw new \Exception('登录验证失败');
        }
        return [$result['dt']['tk'], $result['dt']['geu']];
    }

    public function verifyEx($data)
    {
        $url = sprintf('%s/fofo', $this->gameDomain);
        $info = [
            'command' => 'token',
            'options' => ['currency' => $data['currency']]
        ];
        $body = http_build_query([
            'command' => json_encode($info)
        ]);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'gwcurrency: ' . $data['currency'],
            'gwservertype: ' . $data['game_code'],
            'gwuid: ' . $data['uid'],
            'gwuri: /index',
            'identity: ' . $data['identity']
        ];
        $result = wget($url, $body, [CURLOPT_HTTPHEADER => $headers]);
        if (!$result || empty($result['dt']['tk'])) {
            throw new \Exception('登录验证失败');
        }
        return [$result['dt']['tk'], $result['dt']['geu']];
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

        if (in_array($data['game_code'], self::GAME_CODE_EX)) {
            return $this->submitGameDataEx($data);
        }

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
        } elseif ($querys['game_code'] == 1738001) {
            $body['ps'] = mt_rand(1, 4);
        }

        //验证游戏接口是否存在(服务端spin和Spin接口不同)
        $url = $this->checkSpinApi($gameCode, $path);

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

    public function submitGameDataEx($data) 
    {
        $gameCode = $data['game_code'];
        $tk = $data['tk'];
        $path = $data['path'];
        $querys = $data['querys'];
        // 用于修复数据用
        $saveData = $querys['save_data'] ?? [];
        $id = $querys['id'] ?? 0;

        // 初始化
        $url = sprintf('%s/fofo', $this->gameDomain);
        $info = [
            'command' => 'init',
            'options' => []
        ];
        $body = http_build_query([
            'command' => json_encode($info)
        ]);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'gwcurrency: ' . $querys['currency'],
            'gwservertype: ' . $querys['game_code'],
            'gwuid: ' . $querys['uid'],
            'gwuri: /index',
            'token: ' . $data['tk']
        ];
        $result = wget($url, $body, [CURLOPT_HTTPHEADER => $headers]);
        if (!$result || empty($result['dt']['cs']) || empty($result['dt']['ml'])) {
            throw new \Exception('初始化失败');
        }

        // 跑数据
        $body = [
            'command' => 'play',
            'options' => [
                'cs' => $result['dt']['cs'][0],
                'ml' => $result['dt']['ml'][0],
            ],

            // 跑数据时删除的参数
            'tk' => $tk,
            'currency' => $querys['currency'],
            'uid' => $querys['uid'],
            'game_code' => $gameCode
        ];

        //验证游戏接口是否存在
        $url = $this->checkSpinApi($gameCode, $path);

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

    public function checkSpinApi($gameCode, $path)
    {
        if (in_array($gameCode, self::GAME_CODE_EX)) {
            return sprintf('%s/fofo', $this->gameDomain);
        }

        //验证接口是否存在(服务端spin和Spin接口不同)
        $checkUrl = function ($url) {
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


