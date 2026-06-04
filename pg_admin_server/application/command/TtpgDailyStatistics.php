<?php

namespace app\command;

use app\common\model\database1\Room;
use app\common\model\database1\User;
use app\common\model\Statistics\TtpgDailyStatistics as TtpgDailyStatisticsModel;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\console\Command;

class TtpgDailyStatistics extends Command
{


    protected  $type_id = 2;

    protected function configure()
    {
        $this->setName('ttpg_daily_stats_cron')->setDescription('UUPG每日数据统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('statistics reset_data Crontab job start;');

//        $this->beiyong($output);
        try {
            $today = date('Y-m-d', time());
            $yestorday = date('Y-m-d', time() - 3600 * 24);
            $todayData = TtpgDailyStatisticsModel::where('date_time', $today)->find();
            if (empty($todayData)) {
                $this->time_judgement($output, $yestorday);  //每天只统计一次总金额
                $this->time_judgement($output, $today);
            } else {
                $this->time_judgement($output, $today);
            }
        } catch (\Exception $exception) {
            save_log('statistic_order', $exception->getMessage());
        }
        $output->writeln('statistics reset_data Crontab job end;');
    }


    public function beiyong($output)
    {
        $output->writeln('statistics statistic Crontab job start;' . date('Y-m-d H:i:s', time()));
        $start_date = '2024-08-01';  // 开始日期
        $end_date = date('Y-m-d', time());    // 结束日期
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        while ($current_date <= $end_timestamp) {
            $today = date('Y-m-d', $current_date);
            $yestorday = date('Y-m-d', $current_date - 3600 * 24);
            $todayData = TtpgDailyStatisticsModel::where('date_time', $today)->find();
            if (empty($todayData)) {
                $this->time_judgement($output, $yestorday);  //每天只统计一次总金额
                $this->time_judgement($output, $today);
            } else {
                $this->time_judgement($output, $today);
            }
            $current_date = strtotime('+1 day', $current_date);  // 增加一天
        }
        $output->writeln('statistics statistic Crontab job end;' . date('Y-m-d H:i:s', time()));
    }

    public function time_judgement($output, $yesterday)
    {
        $output->writeln('statistics query data Crontab job start;' . date('Y-m-d H:i:s', time()));
        $model = Model('TigerBetHistory');
        $statistics_array = [];
        $Room = Room::where('status', 1)->where('type_id',$this->type_id)->select();
        $startTime = $yesterday . ' 00:00:00';
        $endTime = $yesterday . ' 23:59:59';
        $startStampTime = strtotime($startTime);
        $endStampTime = strtotime($endTime);

        foreach ($Room as $vo) {
            if (!empty($vo['history_table_name'])) {
                $result = Db::query("SHOW TABLES LIKE '{$vo['history_table_name']}'");
                if ($result) {
                    $where_array = [];
                    $query_data = $model->table($vo['history_table_name'])
                        ->where($where_array)
                        ->where('created_at', 'between', [$startStampTime * 1000, $endStampTime * 1000])
                        ->group('platform_id,merchant_id')
                        ->field("sum(bet) bet_amount,sum(transfer) transfer,merchant_id,platform_id,count(*) bet_times,count(distinct user_id) user_number")
                        ->select();
                    foreach ($query_data as &$vv) {
                        $vv['room_id'] = $vo['id'];
                        $vv['win_amount'] = bcadd($vv['bet_amount'], $vv['transfer']);
                        $statistics_array[] = $vv;
                    }
                }
            }
        }

        $new_data = [];
        $list_data = [];
        foreach ($statistics_array as $vo) {
            $key = $vo['platform_id'] . '---' . $vo['merchant_id'] . '---' . $vo['room_id'];
            $new_data[$key][] = $vo;

        }
        foreach ($new_data as $vo) {
            $this_data = [
                'bet_amount' => 0,
                'transfer' => 0,
                'merchant_id' => 0,
                'platform_id' => 0,
                'user_number' => 0,
                'bet_times' => 0,
                'win_amount' => 0,
            ];
            foreach ($vo as $vv) {
                $this_data['bet_amount'] += $vv['bet_amount'];
                $this_data['transfer'] += $vv['transfer'];
                $this_data['bet_times'] += $vv['bet_times'];
                $this_data['win_amount'] += $vv['win_amount'];
                $this_data['platform_id'] = $vv['platform_id'];
                $this_data['merchant_id'] = $vv['merchant_id'];
                $this_data['room_id'] = $vv['room_id'];
                $this_data['user_number'] = $vv['user_number'];
            }
            $list_data[] = $this_data;
        }
        $output->writeln('statistics statistic Crontab job start;' . date('Y-m-d H:i:s', time()));
        $this->update_data($list_data, $yesterday);
        $output->writeln('statistics statistic Crontab job end;' . date('Y-m-d H:i:s', time()));

    }

    public function update_data($new_list, $yesterday)
    {
        foreach ($new_list as $vo) {
            $save_data = [
                'bet_amount' => $vo['bet_amount'],//押注
                'win_amount' => $vo['win_amount'],//赢金
                'bet_times' => $vo['bet_times'],//押注次数
                'transfer' => $vo['transfer'],//净输赢
                'user_number' => $vo['user_number'],//人数
            ];
            $this_data = TtpgDailyStatisticsModel::where('date_time', $yesterday)
                ->where('platform_id', $vo['platform_id'])
                ->where('merchant_id', $vo['merchant_id'])
                ->where('room_id', $vo['room_id'])
                ->find();
            if (!empty($this_data)) {
                (new TtpgDailyStatisticsModel())->where('date_time', $yesterday)
                    ->where('platform_id', $vo['platform_id'])
                    ->where('merchant_id', $vo['merchant_id'])
                    ->where('room_id', $vo['room_id'])
                    ->update($save_data);
            } else {
                $save_data['date_time'] = $yesterday;
                $save_data['platform_id'] = $vo['platform_id'];
                $save_data['merchant_id'] = $vo['merchant_id'];
                $save_data['room_id'] = $vo['room_id'];
                (new TtpgDailyStatisticsModel())->insert($save_data);
            }
        }
    }


}