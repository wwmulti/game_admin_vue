<?php

namespace app\job;

use think\Cache;
use think\Console;
use think\Db;

// 游戏分析&赢金分析
class GameDataAnalysJob
{

    // 秒级时间戳表
    public $shortTimeTables = ['game_uu_fortune_snake_bet_history'];

    public function fire($job, $data)
    {
        set_time_limit(0);
        try {
            if ($data['type'] == 1) {
                $this->winAnalys($data);
            } else {
                $this->gameAnalys($data);
            }          
        } catch (\Throwable $t) {
            save_log('game_data_analys_error', json_encode([
                'id' => $data['id'],
                'data' => $data,
                'error' => $t->getMessage(),
            ]));
        } finally {
            // 任务处理完成后，删除任务
            $job->delete();
        }
    }

    public function failed($data)
    {
        var_dump('任务达到最大重试次数后，失败处理');
    }

    // 游戏分析
    protected function gameAnalys($data = [])
    {
        $id = $data['id'];
        $model = model('TigerBetHistory');
        $model->table('game_analys_tasks')->where('id', $id)->update(['status' => 1, 's_time' => time()]);

        try {
            $tableName = $data['history_table_name'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'];
            $merchantId = $data['merchant_id'];

            // 判断表类型
            $where = [];
            $isWingo = ($tableName == 'game_wingo_log');
            if ($isWingo) {
                $where['win_status'] = ['neq', 0];
            }

            // 查询最新一条记录，判断created_at是秒级还是毫秒级时间戳
            $lastRecord = $model->table($tableName)->order('id', 'desc')->find();
            $isMillisecond = false;
            if ($lastRecord && isset($lastRecord['created_at'])) {
                $createdAt = $lastRecord['created_at'];
                if (is_numeric($createdAt) && $createdAt > 100000000000) {
                    $isMillisecond = true;
                }
            }

            if ($isMillisecond) {
                $startStampTime = is_numeric($startTime) ? $startTime * 1000 : strtotime($startTime) * 1000;
                $endStampTime = is_numeric($endTime) ? $endTime * 1000 : strtotime($endTime) * 1000;
            } else {
                $startStampTime = is_numeric($startTime) ? $startTime : strtotime($startTime);
                $endStampTime = is_numeric($endTime) ? $endTime : strtotime($endTime);
            }

            //

            if (!empty($merchantId)) {
                $where['merchant_id'] = $merchantId;
            }

            // 查询数据，以rtp_id分组
            $queryData = $model->table($tableName)
                ->where($where)
                ->where('created_at', 'between', [$startStampTime, $endStampTime])
                ->group('rtp_id')
                ->field("
                rtp_id,
                count(*) as bet_times,
                sum(bet) as bet_total,
                sum(bet + transfer) as win_total,
                sum(case when (bet + transfer) > 0 then 1 else 0 end) as win_count
            ")
                ->select();

            // 计算中奖概率和返奖率
            foreach ($queryData as &$row) {
                $row['bet_total'] = $row['bet_total'] ?? 0;
                $row['win_total'] = $row['win_total'] ?? 0;
                $row['win_count'] = $row['win_count'] ?? 0;
                $row['bet_times'] = $row['bet_times'] ?? 0;

                // 中奖概率 = 赢次数 / 下注次数
                $row['win_rate'] = $row['bet_times'] > 0
                    ? round($row['win_count'] / $row['bet_times'] * 100, 2) . '%'
                    : '0%';

                // 返奖率 = 总赢金 / 下注总额
                $row['rtp'] = $row['bet_total'] > 0
                    ? round($row['win_total'] / $row['bet_total'] * 100, 2) . '%'
                    : '0%';
            }

            $model->table('game_analys_tasks')->where('id', $id)->update([
                'status' => 2,
                'e_time' => time(),
                'result' => json_encode($queryData, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Exception $e) {
            $model->table('game_analys_tasks')->where('id', $id)->update([
                'status' => 3,
                'e_time' => time(),
                'remark' => $e->getMessage(),
            ]);
        }
    }

    // 赢金分析
    protected function winAnalys($data = [])
    {
        $id = $data['id'];
        $model = model('TigerBetHistory');
        $model->table('game_analys_tasks')->where('id', $id)->update(['status' => 1, 's_time' => time()]);

        try {
            $tableName = $data['history_table_name'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'];
            $merchantId = $data['merchant_id'];

            // 判断表类型
            $where = [];
            $isWingo = ($tableName == 'game_wingo_log');
            if ($isWingo) {
                $where['win_status'] = ['neq', 0];
            }

            // 查询最新一条记录，判断created_at是秒级还是毫秒级时间戳
            $lastRecord = $model->table($tableName)->order('id', 'desc')->find();
            $isMillisecond = false;
            if ($lastRecord && isset($lastRecord['created_at'])) {
                $createdAt = $lastRecord['created_at'];
                if (is_numeric($createdAt) && $createdAt > 100000000000) {
                    $isMillisecond = true;
                }
            }

            if ($isMillisecond) {
                $startStampTime = is_numeric($startTime) ? $startTime * 1000 : strtotime($startTime) * 1000;
                $endStampTime = is_numeric($endTime) ? $endTime * 1000 : strtotime($endTime) * 1000;
            } else {
                $startStampTime = is_numeric($startTime) ? $startTime : strtotime($startTime);
                $endStampTime = is_numeric($endTime) ? $endTime : strtotime($endTime);
            }

            if (!empty($merchantId)) {
                $where['merchant_id'] = $merchantId;
            }

            // 构建子查询条件
            $subWhere = 'created_at between ' . $startStampTime . ' and ' . $endStampTime;
            if (!empty($merchantId)) {
                $subWhere .= ' and merchant_id = ' . $merchantId;
            }
            if (isset($where['win_status'])) {
                $winStatus = $where['win_status'];
                $subWhere .= ' and win_status ' . ($winStatus[0] == 'neq' ? '!= ' : '= ') . $winStatus[1];
            }

            // 先按user_id+rtp_id汇总每个玩家净额，再统计输赢（每个玩家只属于赢或输一方）
            $subSql = '(select user_id, rtp_id, count(*) as bet_count, sum(transfer) as user_net from ' . $tableName . ' where ' . $subWhere . ' group by user_id, rtp_id) t';
            $queryData = $model->table($subSql)
                ->group('rtp_id')
                ->field("
                    rtp_id,
                    sum(bet_count) as bet_times,
                    count(*) as total_users,
                    count(case when user_net > 0 then 1 end) as win_count,
                    count(case when user_net <= 0 then 1 end) as lose_count
                ")
                ->select();

            $model->table('game_analys_tasks')->where('id', $id)->update([
                'status' => 2,
                'e_time' => time(),
                'result' => json_encode($queryData, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Exception $e) {
            $model->table('game_analys_tasks')->where('id', $id)->update([
                'status' => 3,
                'e_time' => time(),
                'remark' => $e->getMessage(),
            ]);
        }
    }
}
