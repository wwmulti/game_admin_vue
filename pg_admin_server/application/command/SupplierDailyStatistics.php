<?php

namespace app\command;

use app\common\model\database1\Merchant;
use app\common\model\database1\Room;
use app\common\model\database1\User;
use app\common\model\database1\TigerBetHistory;
use app\common\model\Statistics\SupplierDailyStatistics as SupplierDailyStatisticsModel;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\console\Command;
use think\Model;

class SupplierDailyStatistics extends Command
{
    protected function configure()
    {
        $this->setName('sps_daily_stats_cron')->setDescription('游戏每日数据统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('statistics reset_data Crontab job start;');

        // $this->beiyong($output);return;
        try {
            $today =  date('Y-m-d', time());
            $yestorday = date('Y-m-d', time() - 3600 * 24);
            $todayData = SupplierDailyStatisticsModel::where('date_time', $today)->find();
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
        $start_date = '2026-05-18';  // 开始日期
        $end_date = date('Y-m-d', time());    // 结束日期
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        while ($current_date <= $end_timestamp) {
            echo date('Y-m-d', $current_date) . "\n";  // 输出当前日期
            $today = date('Y-m-d', $current_date);
            $yestorday = date('Y-m-d', $current_date - 3600 * 24);
            $todayData = SupplierDailyStatisticsModel::where('date_time', $today)->find();
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
        $model = model('TigerBetHistory');
        $statistics_array = [];
        $Room = Room::where('status', 1)->select();
        $startTime = $yesterday . ' 00:00:00';
        $endTime = $yesterday . ' 23:59:59';
        $startStampTime = strtotime($startTime);
        $endStampTime = strtotime($endTime);

        // 小于100的rtp_id
        $rtpInfo = $model->table('game_rtp')
            ->where('rtp', 100)
            ->find();
        $rtpId = $rtpInfo['id'] ?? 21;

        //新注册用户
        $newUserNumArray = User::where('created_at', 'between', [$startTime, $endTime])
            ->group('platform_id,merchant_id')
            ->field('platform_id,merchant_id,count(*) count')
            ->select();
        $newUserNumData = [];
        foreach ($newUserNumArray as $vo) {
            $key = $vo['platform_id'] . '---' . $vo['merchant_id'];
            $newUserNumData[$key] = $vo['count'];
        }

        foreach ($Room as $vo) {
            if (!empty($vo['history_table_name'])) {
                $result = Db::query("SHOW TABLES LIKE '{$vo['history_table_name']}'");
                if ($result) {
                    $tempStartTime = time();
                    $output->writeln(sprintf('table [%s] runstat start at %s', $vo['history_table_name'], date('Y-m-d H:i:s')));
                    $where_array = [];
                    if($vo['history_table_name'] == 'game_wingo_log'){
                        $where_array['win_status'] = ['neq',0];

                        $query_data = $model->table($vo['history_table_name'])
                            ->where($where_array)
                            ->where('created_at', 'between', [$startStampTime, $endStampTime])
                            ->group('platform_id,merchant_id')
                            ->field("sum(bet) bet_amount,sum(transfer) transfer,merchant_id,platform_id,count(*) bet_times")
                            ->select();

                        $user_data = $model->table($vo['history_table_name'])
                            ->where($where_array)
                            ->where('created_at', 'between', [$startStampTime, $endStampTime])
                            ->group('platform_id,merchant_id,user_id')
                            ->field("merchant_id,platform_id,user_id")
                            ->select();
                    }else{

                        // 检查是否是毫秒级时间戳
                        $lastRecord = $model->table($vo['history_table_name'])->order('id', 'desc')->find();
                        $isMillisecond = false;
                        if ($lastRecord && isset($lastRecord['created_at'])) {
                            $createdAt = $lastRecord['created_at'];
                            if (is_numeric($createdAt) && $createdAt > 100000000000) {
                                $isMillisecond = true;
                            }
                        }

                        if ($isMillisecond) {
                            $startStampTime = $startStampTime * 1000;
                            $endStampTime = $endStampTime * 1000;
                        } 

                        $query_data = $model->table($vo['history_table_name'])
                            ->where($where_array)
                            ->where('created_at', 'between', [$startStampTime, $endStampTime])
                            ->where('rtp_id', '<', $rtpId)
                            ->group('platform_id,merchant_id')
                            ->field("sum(bet) bet_amount,sum(transfer) transfer,merchant_id,platform_id,count(*) bet_times")
                            ->select();

                        $user_data = $model->table($vo['history_table_name'])
                            ->where($where_array)
                            ->where('created_at', 'between', [$startStampTime, $endStampTime])
                            ->where('rtp_id', '<', $rtpId)
                            ->group('platform_id,merchant_id,user_id')
                            ->field("merchant_id,platform_id,user_id")
                            ->select();
                    }
                    foreach ($query_data as &$vv) {
                        $activeUserData = [];
//                        $vv['room_id'] = $vo['id'];
                        $vv['win_amount'] = bcadd($vv['bet_amount'], $vv['transfer']);
                        if ($user_data) {
                            foreach ($user_data as $v) {
                                if ($vv['platform_id'] == $v['platform_id'] && $vv['merchant_id'] == $v['merchant_id']) {
                                    $activeUserData[] = $v['user_id'];
                                }
                            }
                        }
                        if (empty($activeUserData)) {
                            $vv['activeUserData'] = [];
                        } else {
                            $vv['activeUserData'] = $activeUserData;
                        }
                        $statistics_array[] = $vv;
                    }
                    $tempEndTime = time();
                    $output->writeln(sprintf('table [%s] runstat end at %s ,usetime: %ds', $vo['history_table_name'], date('Y-m-d H:i:s'), $tempEndTime - $tempStartTime));     
                }
            }
        }

        $new_data = [];
        $list_data = [];
        foreach ($statistics_array as $vo) {
            $key = $vo['platform_id'] . '---' . $vo['merchant_id'];
            $vo['newUserNum'] = $newUserNumData[$key] ?? 0;
            $new_data[$key][] = $vo;

        }
        foreach ($new_data as $vo) {
            $this_data = [
                'bet_amount' => 0,
                'transfer' => 0,
                'merchant_id' => 0,
                'platform_id' => 0,
                'bet_times' => 0,
                'win_amount' => 0,
                'newUserNum' => 0,
                'activeUserData' => [],
            ];
            foreach ($vo as $vv) {
                $this_data['bet_amount'] += $vv['bet_amount'];
                $this_data['transfer'] += $vv['transfer'];
                $this_data['bet_times'] += $vv['bet_times'];
                $this_data['win_amount'] += $vv['win_amount'];
                $this_data['activeUserData'] = array_merge($this_data['activeUserData'], $vv['activeUserData']);
                $this_data['newUserNum'] = $vv['newUserNum'];
                $this_data['platform_id'] = $vv['platform_id'];
                $this_data['merchant_id'] = $vv['merchant_id'];
            }
            $activeUserData = array_unique($this_data['activeUserData'], SORT_REGULAR);
            $this_data['activeUserNum'] = count($activeUserData);
            unset($this_data['activeUserData']);
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
                'activeUserNum' => $vo['activeUserNum'],//活跃用户数
                'newUserNum' => $vo['newUserNum'],//新注册人数
            ];
            $this_data = SupplierDailyStatisticsModel::where('date_time', $yesterday)
                ->where('platform_id', $vo['platform_id'])
                ->where('merchant_id', $vo['merchant_id'])
                ->find();
            if (!empty($this_data)) {
                (new SupplierDailyStatisticsModel())->where('date_time', $yesterday)
                    ->where('platform_id', $vo['platform_id'])
                    ->where('merchant_id', $vo['merchant_id'])
                    ->update($save_data);
            } else {
                $save_data['date_time'] = $yesterday;
                $save_data['platform_id'] = $vo['platform_id'];
                $save_data['merchant_id'] = $vo['merchant_id'];
                (new SupplierDailyStatisticsModel())->insert($save_data);
            }
        }
    }


}