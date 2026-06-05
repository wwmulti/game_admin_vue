<?php

namespace app\job;

use app\admin\service\game\PgGame;
use app\admin\service\GameFactory;
use app\admin\service\GameService;
use stdClass;
use think\Cache;
use think\Console;
use think\Db;

class CheckGameJob
{
    public function fire($job, $data)
    {

        //状态枚举 0:获取游戏地址失败 1:进入游戏失败 2:接口认证失败 3:游戏接口调用失败 4:正常

        set_time_limit(0);
        try {            
            $redis = Cache::store('redis');
            $toListFunc = function ($data, $status = 0, $url = '', $e = null) use ($redis, $job) {
                $cacheKey = sprintf('game_check:%s:', $data['type']);
                $redis->handler()->lpush($cacheKey, json_encode(['game_code' => $data['game_code'], 'url' => $url, 'status' => $status, 'time' => time(), 'err' => is_object($e) ? $e->getMessage() : $e]));
                $job->delete();
            };

            //获取游戏地址
            $gameInfo = [];
            try {
                $gameInfo = wget($data['url'], json_encode($data['body']));
                if (isset($gameInfo['code']) && $gameInfo['code'] != 0 || empty($gameInfo['data']['url'])) {
                    throw new \Exception('失败：' . $gameInfo['message'] ?? '');
                }
            } catch (\Exception $e) {
                $toListFunc($data, 0, '', $e);
                return;
            }

            //验证游戏地址
            $gameUrl = $gameInfo['data']['url'];
            try {
                wget($gameUrl, [], [], true);
            } catch (\Exception $e) {
                $toListFunc($data, 1, $gameUrl, $e);
                return;
            }

            //游戏
            $game = GameFactory::create($data['type_id']);

            //验证认证接口
            $params = parse_url($gameUrl);
            $querys = [];
            parse_str($params['query'], $querys);
            $querys['game_code'] = $data['game_code'];
            $querys['type_id'] = $data['type_id'];
            try {
                [$tk, $path] = $game->verify($querys);
            } catch (\Exception $e) {
                $toListFunc($data, 2, $gameUrl, $e->getMessage());
                return;
            }

            //验证游戏接口
            try {
                if ($data['type_id'] == 5) { //kess
                    $game->checkSpinApi($game->getGameUri($data['game_code']), $querys);
                } elseif ($data['type_id'] == 2) { // pg
                    $game->checkSpinApi($data['game_code'], $path);
                } else {
                    $game->checkSpinApi($path);
                }                
            } catch (\Throwable $e) {
                $toListFunc($data, 3, $gameUrl, $e->getMessage());
                return;
            }

            $toListFunc($data, 4, $gameUrl);
            
            // 任务处理完成后，删除任务
            $job->delete();

        } catch (\Throwable $t) {
            $job->delete();
        }
    }

    public function failed($data)
    {
        var_dump('任务达到最大重试次数后，失败处理');
    }

}
