<?php

namespace app\common\services;

use app\admin\model\merchant\Merchants;
use app\admin\model\RoomBaseConfig;
use app\admin\model\User;
use app\admin\model\WinGo;
use app\admin\model\WinGoLog;
use app\admin\model\WingoOperationLog;
use app\admin\model\WinList;
use think\Cache;

class WinRoomService
{
    protected static $roomId = 1001;

    /**
     * 获取 winning go 设置列表
     *
     * @param [type] $params
     * @return array
     */
    public static function getWinRoomList($params): array
    {
        $id = $params['id'] ?? null;
        $seconds = $params['seconds'] ?? null;
        $stopSecond = $params['stop_second'] ?? null;
        $odds = $params['odds'] ?? null;
        $weight = $params['weight'] ?? null;
        $status = $params['status'] ?? null;
        $pageSize = $params['pageSize'] ?? 10;
        $list = WinGo::where(function ($query) use ($id, $seconds, $stopSecond, $odds, $weight, $status) {
            if ($id) {
                $query->where('id', $id);
            }
            if ($seconds) {
                $query->where('seconds', $seconds);
            }
            if ($stopSecond) {
                $query->where('stop_second', $stopSecond);
            }
            if ($odds) {
                $query->where('odds', $odds);
            }
            if ($weight) {
                $query->where('weight', $weight);
            }
            if ($status !== null) {
                $query->where('status', (int)$status);
            }
        })->order('id', 'desc')
            ->paginate($pageSize)
            ->toArray();
        foreach ($list['data'] as &$row) {
            if ($row['status'] == 1) {
                $row['status'] = true;
            } else {
                $row['status'] = false;
            }
        }

        return $list;
    }

    /**
     * 添加 winning go 设置
     *
     * @param [type] $params
     * @return void
     */
    public static function addWinGoSet($params)
    {
        $wingo = new WinGo();

        $seconds = $params['seconds'] ?? 0;
        $stopSecond = $params['stop_second'] ?? 0;
        if ($seconds < $stopSecond) {
            throw new \Exception(__('The interval in seconds must not be less than the number of seconds until the early betting deadline'));
        }

        $thisAddId = $wingo->order('id', 'desc')->value('id');
        $thisAddId++;
        if ($thisAddId >= 10) {
            throw new \Exception(__('The limit for adding IDs has been exceeded; it cannot exceed 10'));
        }

        $wingo->seconds = $params['seconds'];
        $wingo->stop_second = $params['stop_second'];
        $wingo->odds = $params['odds'];
        $wingo->weight = $params['weight'];
        $wingo->status = $params['status'];
        $wingo->create_time = time();
        $result = $wingo->save();

        if ($result === false) {
            throw new \Exception(__('No rows were inserted'));
        }
    }

    /**
     * 编辑 winning go 设置
     *
     * @param [type] $params
     * @return void
     */
    public static function editWinGoSet($params)
    {
        $wingo = WinGo::where('id', $params['id'])->find();
        if (!$wingo) {
            throw new \Exception(__('No Results were found'));
        }

        $seconds = $params['seconds'] ?? 0;
        $stopSecond = $params['stop_second'] ?? 0;
        if ($seconds < $stopSecond) {
            throw new \Exception(__('The interval in seconds must not be less than the number of seconds until the early betting deadline'));
        }

        $wingo->seconds = $params['seconds'];
        $wingo->stop_second = $params['stop_second'];
        $wingo->odds = $params['odds'];
        $wingo->weight = $params['weight'];
        $wingo->status = (int)$params['status'];
        $result = $wingo->save();

        if ($result === false) {
            throw new \Exception(__('No rows were updated'));
        }
    }

    /**
     * 删除 winning go 设置
     *
     * @param [type] $params
     * @return void
     */
    public static function delWinGoSet($params)
    {
        $id = $params['id'] ?? null;
        if (strpos($id, ',') !== false) {
            $id = explode(',', $id);
        } else {
            $id = [$id];
        }
        $wingo = WinGo::where('id', 'in', $id)->select();
        if (!$wingo) {
            throw new \Exception(__('No Results were found'));
        }
        WinGo::destroy($id);
    }

    /**
     * 切换 winning go 设置
     *
     * @param [type] $params
     * @return void
     */
    public static function winGoSetSwitch($params)
    {
        $wingo = WinGo::where('id', $params['id'])->find();
        if ($wingo->status == 1) {
            $wingo->status = 0;
        } else {
            $wingo->status = 1;
        }
        $wingo->save();
    }

    /**
     * 获取 winning go 操作日志列表
     *
     * @param [type] $params
     * @return array
     */
    public static function winGoOperationLog($params): array
    {
        $id = $params['id'] ?? null; 
        $adminId = $params['admin_id'] ?? null;
        $roomId = $params['room_id'] ?? null;
        $issue = $params['issue'] ?? null;
        $number = $params['number'] ?? null;
        list($startDate, $endDate) = request()->get('time/a');
        $pageSize = $params['pageSize'] ?? 10;
        $list = WingoOperationLog::where(function ($query) use ($params, $id, $adminId, $roomId, $issue, $number, $startDate, $endDate) {
            if ($id) {
                $query->where('id', $id);
            }
            if ($adminId) {
                $query->where('admin_id', $adminId);
            }

            if ($roomId) {
                $query->where('room_id', $roomId);
            }
            if ($issue) {
                $query->where('issue', 'like', '%' . $issue . '%');
            }
            if (isset($params['number'])) {
                $query->where('number', $number);
            }
            
            if (!empty($startDate) && !empty($endDate)) {
                $condition['time'] = [['>=', strtotime($startDate)], ['<', strtotime('+1days', strtotime($endDate))]];
                $query->where($condition);
            }
            
        })->order('id', 'desc')
            ->paginate($pageSize)
            ->toArray();
            foreach ($list['data'] as &$row) {
                $row['time'] = date('Y-m-d H:i:s', $row['time']);
            }
        
        return $list;
    }

    /**
     * 获取 winning go 房间列表
     *
     * @return array
     */
    public static function wingoRoom(): array
    {
        $list = WinGo::where('status', 1)->order('weight desc')->field('id,seconds')->select();
        foreach ($list as &$vo) {
            $seconds = $vo['seconds'];
            $vo['formatted_time'] = SetTime($seconds);
        }
        return $list;
    }

    /**
     * 获取 winning go 房间名称列表
     *
     * @return array
     */
    public static function roomName(): array
    {
        $winGoList = WinGo::where('status', 1)->order('weight desc')->column('id,seconds');
        foreach ($winGoList as &$vo) {
            $vo = SetTime($vo);
        }
        return $winGoList;
    }

    /**
     * 预设值开奖信息
     *
     * @param [type] $params
     * @return bool
     */
    public static function winningSubmit($params): bool
    {
        $room_id = $params['room_id']; //房间ID
        $issue = $params['issue']; //期号
        $winning_numbers = $params['winning_numbers']; //预设值开奖号码
        $lates_win_go = WinList::where('room_id', $room_id)->where('issue', $issue)
            ->find();
        if ($lates_win_go) {
            $RoomData = WinGo::where('id', $room_id)->find();
            if (!$RoomData) {
                throw new \Exception(__('Room not found'));
            }
            if ($lates_win_go['draw_time_pre'] - $RoomData['settle_second'] < time()) {
                throw new \Exception(__('Time is not within the settable range'));
            }
            if (!($winning_numbers >= 0 && $winning_numbers <= 9)) {
                throw new \Exception(__('The preset winning numbers are not in the range of 0-9'));
            }

            WinList::where('room_id', $room_id)->where('issue', $issue)->update([
                'number_pre' => $winning_numbers
            ]);
            (new WingoOperationLog)->add_data($room_id, $issue, $winning_numbers, $params['auth_id']);
            return true;
        } else {
            throw new \Exception(__('No period exists'));
        }
        return false;
    }

    /**
     * 获取房间总统计
     *
     * @param [type] $params
     * @return array
     */
    public static function latestData($params): array
    {
        /**
         * transfer =>净输赢
         * win_amount =>中奖金额
         * sum_amount =>总下注金额
         * win_number =>中奖人数
         * digit_amount =>0，9下注金额        *9
         * size_amount =>猜大小下注金额        *2
         * colour_amount =>猜颜色下注金额      *2
         * purple_amount =>0，5紫色下注金额    *4.5
         * special_amount =>0，5绿色、红色     *1.5
         */
        $room_lates_data = [
            0 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            1 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            2 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            3 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            4 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            5 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            6 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            7 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            8 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
            9 => ['transfer' => 0, 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0,],
        ];
        $room_id = $params['room_id'] ?? null;
        $lates_win_go = WinList::where('room_id', $room_id)
            ->where('status', 0)
            ->order('id asc')
            ->find();
        if (!empty($lates_win_go)) {
            $issue = $lates_win_go['issue']; //最新期数
            $list = collection(WinGoLog::where('room_id', $room_id)
                ->where('issue', $issue)
                ->field('user_id,number,bet')
                ->select())->toArray();
            foreach ($room_lates_data as $k => &$vo) {
                $data = self::detailsStatistics($room_id, $k, $list);
                if ($data) {
                    $vo['transfer'] = $data['total_data']['sum_transfer'];
                    $vo['win_amount'] = $data['total_data']['win_amount'];
                    $vo['sum_amount'] = $data['total_data']['sum_amount'];
                    $vo['win_number'] = $data['total_data']['win_number'];
                }
            }
        }



        $new_array = [];

        $new_array[0]['name'] = [
            'name' => '系统净输赢',
            'color' => '#4dbb8c',
        ];
        $new_array[1]['name'] = [
            'name' => '中奖金额',
            'color' => '#6bbbee',
        ];
        $new_array[2]['name'] = [
            'name' => '中奖人数',
            'color' => '#ce6e58',
        ];
        foreach ($room_lates_data as $key => $vo) {
            $new_array[0][] = $vo['transfer'];
            $new_array[1][] = $vo['win_amount'];
            $new_array[2][] = $vo['win_number'];
        }

        return $new_array;
    }

    /**
     * 房间详细信息
     *
     * @param [type] $params
     * @return array
     */
    public static function details($params): array
    {
        $room_id = $params['room_id'] ?? null;
        $number = $params['number'] ?? null;
        $data = self::detailsStatistics($room_id, $number);
        $new_array = [];
        $new_tab = [];
        $new_array[0]['name'] = [
            'name' => '下注金额',
            'color' => '#4dbb8c',
        ];
        $new_array[1]['name'] = [
            'name' => '投注次数',
            'color' => '#6bbbee',
        ];
        $new_array[2]['name'] = [
            'name' => '中奖人数',
            'color' => '#ce6e58',
        ];
        $new_array[3]['name'] = [
            'name' => '预估返还',
            'color' => '#efb963',
        ];
        $new_array[4]['name'] = [
            'name' => '盈亏',
            'color' => '#4dbb8c',
        ];
        foreach ($data['list'] as $key => $vo) {
            if ($key !== 13 && $key !== 14) {
                if (is_int($vo['name'])) {
                    $new_tab[] = [
                        'color' => "#6abcee",
                        'name' => '数字 ' . $vo['name'],
                    ];
                } else {
                    if ($vo['name'] == '绿色') {
                        $new_tab[] = [
                            'color' => "#49bb73",
                            'name' => $vo['name'],
                        ];
                    } else if ($vo['name'] == '红色') {
                        $new_tab[] = [
                            'color' => "#d86962",
                            'name' => $vo['name'],
                        ];
                    } else if ($vo['name'] == '紫色') {
                        $new_tab[] = [
                            'color' => "#a9369e",
                            'name' => $vo['name'],
                        ];
                    }
                    //                    else if($vo['name'] == '大'){
                    //                        $new_tab[] = [
                    //                            'color'=>"#d86962",
                    //                            'name'=>$vo['name'],
                    //                        ];
                    //                    }else if($vo['name'] == '小'){
                    //                        $new_tab[] = [
                    //                            'color'=>"#49bb73",
                    //                            'name'=>$vo['name'],
                    //                        ];
                    //                    }
                    else {
                        $new_tab[] = [
                            'color' => "#6abcee",
                            'name' => '数字 ' . $vo['name'],
                        ];
                    }
                }
                $new_array[0][] = $vo['sum_amount'];
                $new_array[1][] = $vo['bet_number'];
                $new_array[2][] = $vo['win_number'];
                $new_array[3][] = $vo['win_amount'];
                $new_array[4][] = $vo['transfer'];
            }
        }
        $return = [
            'list' => $new_array,
            'new_tab' => $new_tab,
            'total_data' => $data['total_data'],
            'draw_time' => $data['draw_time'],
            'issue' => $data['issue'],
            'number_pre' => $data['number_pre'],
        ];
        return $return;
    }

    /**
     * 获取详细数据统计
     * @param $room_id
     * @param $number
     * @param $list
     * 封装详细信息-》总统计
     */
    public static function detailsStatistics($room_id, $number, $list = [])
    {
        $room_lates_data = [
            100 => ['transfer' => 0, 'name' => '绿色', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            101 => ['transfer' => 0, 'name' => '红色', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            102 => ['transfer' => 0, 'name' => '紫色', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            0 => ['transfer' => 0, 'name' => '0', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            1 => ['transfer' => 0, 'name' => '1', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            2 => ['transfer' => 0, 'name' => '2', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            3 => ['transfer' => 0, 'name' => '3', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            4 => ['transfer' => 0, 'name' => '4', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            5 => ['transfer' => 0, 'name' => '5', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            6 => ['transfer' => 0, 'name' => '6', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            7 => ['transfer' => 0, 'name' => '7', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            8 => ['transfer' => 0, 'name' => '8', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            9 => ['transfer' => 0, 'name' => '9', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            1000 => ['transfer' => 0, 'name' => '大', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
            1001 => ['transfer' => 0, 'name' => '小', 'win_amount' => 0, 'sum_amount' => 0, 'win_number' => 0, 'bet_number' => 0, 'user_data' => []],
        ];
        $total_data = [
            'sum_amount' => 0, //总投注
            'win_amount' => 0, //总中奖金额
            'bet_number' => 0, //总下注次数
            'win_number' => 0, //总中奖人数
            'sum_fee' => 0, //总手续费
            'sum_transfer' => 0, //总盈亏
        ];
        $lates_win_go = WinList::where('room_id', $room_id)
            ->where('status', 0)
            ->order('id asc')->find();
        if (!empty($lates_win_go)) {
            $issue = $lates_win_go['issue']; //最新期数
            if (empty($list)) {
                $list = collection(WinGoLog::where('room_id', $room_id)
                    ->where('issue', $issue)
                    ->field('user_id,number,bet')
                    ->select())->toArray();
            }
            /**
             * sum_amount =>总下注金额
             * bet_number =>投注次数
             * win_number =>中奖人数
             * win_amount =>预计返还
             * transfer =>盈亏
             * temporary_amount =>临时预估赢取
             */
            foreach ($list as $vo) {
                $room_lates_data[$vo['number']]['user_data'][$vo['user_id']] = $vo['user_id'];
                $room_lates_data[$vo['number']]['sum_amount'] += $vo['bet'];
                $room_lates_data[$vo['number']]['bet_number']++;
            }
            $room_rate = RoomBaseConfig::where('room_id', self::$roomId)->value('ext1');
            $room_rate = $room_rate ? bcdiv($room_rate, 10000, 4) : 0;
            foreach ($room_lates_data as $key => &$vo) {
                $multiplier = 0;
                switch ($key) {
                    case 100:
                        $multiplier = $number == 5 ? 1.5 : (in_array($number, [1, 3, 7, 9]) ? 2 : $multiplier);
                        break;
                    case 101:
                        $multiplier = $number == 0 ? 1.5 : (in_array($number, [2, 4, 6, 8]) ? 2 : $multiplier);
                        break;
                    case 102:
                        $multiplier = in_array($number, [0, 5]) ? 4.5 : $multiplier;
                        break;
                    case 1000:
                        $multiplier = in_array($number, [0, 1, 2, 3, 4]) ? 2 : $multiplier;
                        break;
                    case 1001:
                        $multiplier = in_array($number, [5, 6, 7, 8, 9]) ? 2 : $multiplier;
                        break;
                    default:
                        $multiplier = $key == $number ? 2 : $multiplier;
                        break;
                }
                $vo['room_rate'] = $room_rate;
                $vo['fee'] = bcmul($vo['sum_amount'], $room_rate, 2);
                $vo['win_amount'] = $vo['sum_amount'] * $multiplier;
                if ($vo['win_amount'] > 0) {
                    $vo['win_amount'] = bcsub($vo['win_amount'], $vo['fee'], 2);
                    $vo['win_number'] = count($vo['user_data']);
                }
                $vo['transfer'] = bcsub($vo['sum_amount'], $vo['win_amount'], 2);
                unset($vo['user_data']);
                $vo['transfer'] = FormatMoney($vo['transfer']);
                $vo['win_amount'] = FormatMoney($vo['win_amount']);
                $vo['sum_amount'] = FormatMoney($vo['sum_amount']);
                $vo['fee'] = FormatMoney($vo['fee']);
                $total_data['sum_amount'] = bcadd($total_data['sum_amount'], $vo['sum_amount'], 2);
                $total_data['win_amount'] = bcadd($total_data['win_amount'], $vo['win_amount'], 2);
                $total_data['bet_number'] = bcadd($total_data['bet_number'], $vo['bet_number']);
                $total_data['win_number'] = bcadd($total_data['win_number'], $vo['win_number']);
                $total_data['sum_fee'] = bcadd($total_data['sum_fee'], $vo['fee'], 2);
                $total_data['sum_transfer'] = bcadd($total_data['sum_transfer'], $vo['transfer'], 2);
            }
            foreach ($room_lates_data as $key => &$vv) {
                $vv['key'] = $key;
                switch ($key) {
                    case 100:
                        $vv['name'] = '绿色';
                        break;
                    case 101:
                        $vv['name'] = '红色';
                        break;
                    case 102:
                        $vv['name'] = '紫色';
                        break;
                    case 1000:
                        $vv['name'] = '小';
                        break;
                    case 1001:
                        $vv['name'] = '大';
                        break;
                    default:
                        $vv['name'] = $key;
                        break;
                }
            }
            $room_lates_data = array_values($room_lates_data);


            $lates_win_go['number_pre'] = $lates_win_go['number_pre'] == -1 ? '' : $lates_win_go['number_pre'];
            if (empty($lates_win_go['number_pre'])) {
                $lates_win_go['number_pre'] = $lates_win_go['number_rand'];
            }

            $return_data = [
                'list' => $room_lates_data,
                'total_data' => $total_data,
                'draw_time' => $lates_win_go['draw_time_pre'] - time() < 0 ? 0 : $lates_win_go['draw_time_pre'] - time(),
                'issue' => $issue,
                'number_pre' => $lates_win_go['number_pre'] === -1 ? "未设定" : $lates_win_go['number_pre'],
            ];
            return $return_data;
        } else {
            foreach ($room_lates_data as $key => &$vv) {
                $vv['key'] = $key;
                switch ($key) {
                    case 100:
                        $vv['name'] = '绿色';
                        break;
                    case 101:
                        $vv['name'] = '红色';
                        break;
                    case 102:
                        $vv['name'] = '紫色';
                        break;
                    case 1000:
                        $vv['name'] = '小';
                        break;
                    case 1001:
                        $vv['name'] = '大';
                        break;
                    default:
                        $vv['name'] = $key;
                        break;
                }
            }
            $room_lates_data = array_values($room_lates_data);
            return [
                'list' => $room_lates_data,
                'total_data' => $total_data,
                'draw_time' => 0,
                'issue' => '',
                'number_pre' => '未设定',
            ];
        }
    }

    /**
     * 获取 winning go 预估记录列表
     *
     * @param [type] $params
     * @return array
     */
    public static function wingoForecastLogs($params): array
    {
        $id = $params['id'] ?? null;
        $issue = $params['issue'] ?? null;
        $roomId = $params['room_id'] ?? null;
        $numberRand = $params['number_rand'] ?? null;
        $numberPre = $params['number_pre'] ?? null;
        $number = $params['number'] ?? null;
        $status = $params['status'] ?? 0;
        list($startDate, $endDate) = request()->get('draw_time_pre/a');
        list($startDate1, $endDate1) = request()->get('draw_time/a');
        $pageSize = $params['pageSize'] ?? 10;
        $sort = $params['sort'] ?? 'draw_time_pre';
        $order = $params['order'] ?? 'desc';
        
        $list = WinList::where(function ($query) use ($params, $id, $issue, $roomId, $numberRand, $numberPre, $number, $status, $startDate, $endDate, $startDate1, $endDate1) {
            if ($id) {
                $query->where('id', $id);
            }
            if ($issue) {
                $query->where('issue', $issue);
            }
            if ($roomId) {
                $query->where('room_id', $roomId);
            }
            if ($numberRand) {
                $query->where('number_rand', $numberRand);
            }
            if ($numberPre) {
                $query->where('number_pre', $numberPre);
            }
            if ($number) {
                $query->where('number', $number);
            }
            if (isset($params['status']) && $status !== null) {
                $query->where('status', $status);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $condition['draw_time_pre'] = [['>=', strtotime($startDate)], ['<', strtotime('+1days', strtotime($endDate))]];
                $query->where($condition);
            }

            if (!empty($startDate1) && !empty($endDate1)) {
                $condition['draw_time'] = [['>=', strtotime($startDate1)], ['<', strtotime('+1days', strtotime($endDate1))]];
                $query->where($condition);
            }
        })->order($sort, $order)
            ->paginate($pageSize)
            ->toArray();

        $WinGoList = WinGo::where('status', 1)->order('seconds asc')->column('id,seconds');
        foreach ($WinGoList as &$vo) {
            $vo = SetTime($vo);
        }

        foreach ($list['data'] as &$row) {
            $row['number_pre'] = $row['number_pre'] == -1 ? '-' : $row['number_pre'];
            $row['number'] = $row['number'] == -1 ? '' : $row['number'];
            $row['room_ids'] = $row['room_id'];
            $row['room_id'] = $WinGoList[$row['room_id']] ?? '';
            $row['draw_time_pre'] = $row['draw_time_pre'] > 0 ? date('Y-m-d H:i:s', $row['draw_time_pre']) : '无';
            $row['draw_time'] = $row['draw_time'] > 0 ? date('Y-m-d H:i:s', $row['draw_time']) : '无';
        }
        return $list;
    }

    /**
     * wingo单局下注详情
     *
     * @param [type] $params
     * @return array
     */ 
    public static function wingoLogs($params): array
    {
        $room_id = $params['room_id'];
        $issue = $params['issue'];
        
        $list = WinGoLog::where('room_id', $room_id)
            ->where('issue', $issue)
            ->group('user_id')
            ->order('id', 'desc')
            ->paginate(10);
        $user_ids = [];
        foreach ($list as $vo) {
            $user_ids[] = $vo['user_id'];
        }
        $UserData = User::where('id', 'in', implode(',', $user_ids))->column('id,remote_uid,money');
        foreach ($UserData as &$vu) {
            $vu['remote_uid'] = preg_replace('/[^0-9]/', '', $vu['remote_uid']);
            $vu['money'] = FormatMoney($vu['money']);
        }
        $new_array = [];
        $list_data = WinGoLog::where('room_id', $room_id)
            ->where('issue', $issue)
            ->where('user_id', 'in', implode(',', $user_ids))
            ->select();
        $ids = [];
        foreach ($list_data as $vo) {
            $ids[] = $vo['user_id'];
            $vo['bet'] = FormatMoney($vo['bet']);
            $new_array[$vo['user_id']]['user_id'] = $vo['user_id'];
            $new_array[$vo['user_id']]['remote_uid'] = $UserData[$vo['user_id']]['remote_uid'] ?? '';
            $new_array[$vo['user_id']]['money'] = $UserData[$vo['user_id']]['money'] ?? '';
            $new_array[$vo['user_id']]['issue'] = $vo['issue'];
            $new_array[$vo['user_id']]['room_id'] = $vo['room_id'];
            $new_array[$vo['user_id']]['sum_bet'] = isset($new_array[$vo['user_id']]['sum_bet']) ? $new_array[$vo['user_id']]['sum_bet'] + $vo['bet'] : $vo['bet'];
            switch ($vo['number']) {
                case 0:
                    $new_array[$vo['user_id']]['zero'] = isset($new_array[$vo['user_id']]['zero']) ? $new_array[$vo['user_id']]['zero'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1:
                    $new_array[$vo['user_id']]['one'] = isset($new_array[$vo['user_id']]['one']) ? $new_array[$vo['user_id']]['one'] + $vo['bet'] : $vo['bet'];
                    break;
                case 2:
                    $new_array[$vo['user_id']]['two'] = isset($new_array[$vo['user_id']]['two']) ? $new_array[$vo['user_id']]['two'] + $vo['bet'] : $vo['bet'];
                    break;
                case 3:
                    $new_array[$vo['user_id']]['three'] = isset($new_array[$vo['user_id']]['three']) ? $new_array[$vo['user_id']]['three'] + $vo['bet'] : $vo['bet'];
                    break;
                case 4:
                    $new_array[$vo['user_id']]['four'] = isset($new_array[$vo['user_id']]['four']) ? $new_array[$vo['user_id']]['four'] + $vo['bet'] : $vo['bet'];
                    break;
                case 5:
                    $new_array[$vo['user_id']]['five'] = isset($new_array[$vo['user_id']]['five']) ? $new_array[$vo['user_id']]['five'] + $vo['bet'] : $vo['bet'];
                    break;
                case 6:
                    $new_array[$vo['user_id']]['six'] = isset($new_array[$vo['user_id']]['six']) ? $new_array[$vo['user_id']]['six'] + $vo['bet'] : $vo['bet'];
                    break;
                case 7:
                    $new_array[$vo['user_id']]['seven'] = isset($new_array[$vo['user_id']]['seven']) ? $new_array[$vo['user_id']]['seven'] + $vo['bet'] : $vo['bet'];
                    break;
                case 8:
                    $new_array[$vo['user_id']]['eight'] = isset($new_array[$vo['user_id']]['eight']) ? $new_array[$vo['user_id']]['eight'] + $vo['bet'] : $vo['bet'];
                    break;
                case 9:
                    $new_array[$vo['user_id']]['nine'] = isset($new_array[$vo['user_id']]['nine']) ? $new_array[$vo['user_id']]['nine'] + $vo['bet'] : $vo['bet'];
                    break;
                case 100:
                    $new_array[$vo['user_id']]['green'] = isset($new_array[$vo['user_id']]['green']) ? $new_array[$vo['user_id']]['green'] + $vo['bet'] : $vo['bet'];
                    break;
                case 101:
                    $new_array[$vo['user_id']]['red'] = isset($new_array[$vo['user_id']]['red']) ? $new_array[$vo['user_id']]['red'] + $vo['bet'] : $vo['bet'];
                    break;
                case 102:
                    $new_array[$vo['user_id']]['purple'] = isset($new_array[$vo['user_id']]['purple']) ? $new_array[$vo['user_id']]['purple'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1000:
                    $new_array[$vo['user_id']]['small'] = isset($new_array[$vo['user_id']]['small']) ? $new_array[$vo['user_id']]['small'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1001:
                    $new_array[$vo['user_id']]['large'] = isset($new_array[$vo['user_id']]['large']) ? $new_array[$vo['user_id']]['large'] + $vo['bet'] : $vo['bet'];
                    break;
            }
            $new_array[$vo['user_id']]['user_id'] = $vo['user_id'];
        }
        self::batchUpdateBalance(implode(',', $ids));
        $room_lates_data = array_values($new_array);
        $result = array("total" => $list->total(), "rows" => $room_lates_data);
        return $result;
    }

    /**
     * 批量更新余额
     *
     * @param [type] $ids
     * @return void
     */
    public static function batchUpdateBalance($ids): void
    {
        $user_update_cache = Cache::get('user_update_cache');
        if (empty($user_update_cache)) {
            $UserLise = User::where('id', 'in', $ids)->field('id,merchant_id,remote_uid')->select();
            $MerchantsData = Merchants::column('id,token,secret,cash_get_url');
            foreach ($UserLise as $vo) {
                $post_data = [
                    'UseID' => $vo['remote_uid'],
                    'OperatorToken' => $MerchantsData[$vo['merchant_id']]['token'],
                    'SecretStr' => $MerchantsData[$vo['merchant_id']]['secret'],
                ];
                if (!empty($MerchantsData[$vo['merchant_id']]['cash_get_url'])) {
                    $return_data = self::curl($MerchantsData[$vo['merchant_id']]['cash_get_url'], $post_data);
                    if (isset($return_data['data']['balance_amount']) && $return_data) {
                        User::where('id', $vo['id'])->update(['money' => $return_data['data']['balance_amount']]);
                    }
                }
            }
            Cache::set('user_update_cache', date('Y-m-d H:i:s'), 30);
        }
    }

    /**
     * 更新余额
     *
     * @param [type] $params
     * @return bool
     */
    public static function updateBalance($params): bool
    {
        $row = $params;
        $user_id = $row['user_id'];
        $this_user = User::where('id', $user_id)->find();
        if ($this_user) {
            $MerchantsData = Merchants::where('id', $this_user['merchant_id'])->find();
            if ($MerchantsData) {
                $post_data = [
                    'UseID' => $this_user['remote_uid'],
                    'OperatorToken' => $MerchantsData['token'],
                    'SecretStr' => $MerchantsData['secret'],
                ];
                $return_data = self::curl($MerchantsData['cash_get_url'], $post_data);
                if (isset($return_data['data']['balance_amount']) && $return_data) {
                    User::where('id', $user_id)->update(['money' => $return_data['data']['balance_amount']]);
                } else {
                    throw new \Exception(__('Update failed; the API did not return the latest balance'));
                }
                return true;
            } else {
                throw new \Exception(__('The channel does not exist'));
                
            }
        } else {
            throw new \Exception(__('User does not exist'));
        }
        return false;
    }

    /**
     * 发送请求
     *
     * @param [type] $url
     * @param [type] $data
     * @param [type] $is_json
     * @return void
     */
    public static function curl($url = '', $data = "", $is_json = true)
    {
        $curl_con = curl_init($url);
        $data = json_encode($data);
        curl_setopt($curl_con, CURLOPT_POST, 1);
        curl_setopt($curl_con, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_con, CURLOPT_HEADER, 0);
        curl_setopt($curl_con, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl_con, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl_con);
        $status = curl_getinfo($curl_con);
        curl_close($curl_con);

        if (isset($status['http_code']) && $status['http_code'] == 200) {
            return $is_json ? json_decode($res, true) : $res;
        } else {
            return FALSE;
        }
    }
    
}
