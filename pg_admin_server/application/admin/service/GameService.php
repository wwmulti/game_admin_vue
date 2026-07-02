<?php

namespace app\admin\service;

use app\admin\model\Room;
use app\job\CheckGameJob;
use app\job\GameDataJob;
use think\Cache;
use think\Db;
use think\Queue;
use app\admin\model\RoomType;

class GameService
{

    /**
     * 游戏类型
     */
    public $gameTypes;

    public function __construct()
    {
        $this->gameTypes = RoomType::where('status', 1)->column('id,name');
    }


    /**
     * 登录验证
     */
    public function verify($data)
    {
        $game = GameFactory::create($data['type_id']);
        return $game->verify($data);
    }

    /**
     * 队列跑数据
     */
    public function runQueue($gameCode, $tk, $path, $querys)
    {
        $pattern = 'queues:game_data_batch*';
        $iterator = null;
        $keys = [];
        $redis = Cache::store('redis')->handler();
        do {
            $result = $redis->scan($iterator, $pattern, 100);
            if ($result) {
                $keys = array_merge($keys, $result);
            }
        } while ($iterator > 0);
        if (!empty($keys)) {
            throw new \Exception('有进行中的任务，请稍候');
        }
        
        $data = [
            'game_code' => $gameCode,
            'tk' => $tk,
            'path' => $path,
            'querys' => $querys
        ];
        $game = GameFactory::create($querys['type_id']);
        return $game->submitGameData($data);
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
        $games = Room::where('status', 1)->column('name', 'id');
        $gameTypes = $this->gameTypes;
        $list = Db::table('game_run_stat')->where($where)->order($sort, $order)->paginate($limit)->each(function ($val, $key) use ($games, $gameTypes) {
            // $val['game_name'] = $games[$val['game_code']] ?? '未知';
            $val['game_code'] = $games[$val['game_code']] ?? $val['game_code'];
            $val['type_id'] = $gameTypes[$val['type_id']] ?? $val['type_id'];
            $temp = json_decode($val['result'], true);
            if (empty($temp)) return $val;
            $val['lose'] = ($temp['0'] ?? 0) + ($temp['0~1'] ?? 0);
            $win = array_sum(array_values($temp)) - $val['lose'];
            $val['lose'] = sprintf('%s 【%.2f%%】', $val['lose'], $val['sus'] == 0 ? 0 : ($val['lose'] / $val['sus'] * 100));
            $val['win'] = sprintf('%s 【%.2f%%】', $win, $val['sus'] == 0 ? 0 : ($win / $val['sus'] * 100));
            foreach ($temp as $key => $value) {
                $val[$key] = sprintf('%s 【%.2f%%】', $value, $val['sus'] == 0 ? 0 : ($value / $val['sus'] * 100));
            }
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
        $games = Room::where('status', 1)->column('name', 'id');
        $log['game_name'] = $games[$log['game_code']] ?? '未知';
        $gameTypes = $this->gameTypes;
        $log['type_id'] = $gameTypes[$log['type_id']] ?? $log['type_id'];
        $log['result'] = $result;
        $log['lose'] = ($temp['0'] ?? 0) + ($temp['0~1'] ?? 0);
        $win = $total - $log['lose'];
        $log['lose'] = sprintf('%s 【%.2f%%】', $log['lose'], $log['sus'] == 0 ? 0 : ($log['lose'] / $log['sus'] * 100));
        $log['win'] = sprintf('%s 【%.2f%%】', $win, $log['sus'] == 0 ? 0 : ($win / $log['sus'] * 100));
        return $log;
    }

    /**
     * 游戏入队列检测
     */
    public function checkGamesQueue($params)
    {
        $gameType = $params['type'] ?? 'pg'; //默认pg
        $typeId = array_search($gameType, $this->gameTypes);
        if (!$typeId) {
            throw new \Exception('游戏类型不存在');
        }

        $game = GameFactory::create($typeId);
        $game->submitCheckGame($params);
    }

    public function getGameCheckResult($type)
    {
        $status = [0 => '获取游戏地址失败', 1 => '进入游戏失败', 2 => '接口认证失败', 3 => '游戏接口调用失败', 4 => '正常'];
        $gameTypes = $this->gameTypes;
        $gameType = $type ?? 'pg'; //默认pg
        $typeId = array_search($gameType, $gameTypes);

        $games = Room::where('status', 1)->where('type_id', $typeId)->column('name', 'id');
        $len = Cache::store('redis')->handler()->llen(sprintf('game_check:%s:', $type));
        $list = Cache::store('redis')->handler()->lrange(sprintf('game_check:%s:', $type), 0, $len);
        $result = [];
        foreach ($list as $val) {
            $val = json_decode($val, true);
            $val['game_name'] = $games[$val['game_code']] ?? $val['game_code'];
            $val['status'] = $status[$val['status']] ?? $val['status'];
            $val['time'] = date('Y-m-d H:i:s', $val['time']);
            $result[$val['status']][] = $val;
        }
        krsort($result);
        $ret = [];
        foreach ($result as $v) {
            $ret = array_merge($ret, $v);
        }
        return ['result' => $ret, 'diff' => count($games) - count($ret)];
    }
}
