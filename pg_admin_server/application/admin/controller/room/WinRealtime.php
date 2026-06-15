<?php

namespace app\admin\controller\room;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\WinGo;
use app\admin\model\WinGoLog;
use app\admin\model\WinList;
use app\admin\model\RoomBaseConfig;
use app\admin\model\WingoOperationLog;

/**
 * 游戏期数配置管理
 */
class WinRealtime extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['wingo_room', 'room_name', 'details', 'LatestData'];
    protected $room_id = 1001;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WinList;

    }

    public function wingoRoom()
    {
        $list = WinGo::where('status', 1)->order('weight desc')->field('id,seconds')->select();
        foreach ($list as &$vo) {
            $seconds = $vo['seconds'];
            $vo['formatted_time'] = SetTime($seconds);
        }
        return $this->jsonSucc($list);
    }

    public function room_name()
    {
        $WinGoList = WinGo::where('status', 1)->order('weight desc')->column('id,seconds');
        foreach ($WinGoList as &$vo) {
            $vo = SetTime($vo);
        }
        return $this->jsonSucc($WinGoList);
    }

    /**
     * 查看
     */
    public function index()
    {
        list($where, $sort, $order, $offset, $limit) = $this->buildparams(['draw_time_pre' => 'times', 'draw_time' => 'times']);

        $WinGoList = WinGo::where('status', 1)->order('seconds asc')->column('id,seconds');
        foreach ($WinGoList as &$vo) {
            $vo = SetTime($vo);
        }
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        foreach ($list as &$vo) {
            $vo['number_pre'] = $vo['number_pre'] == -1 ? '-' : $vo['number_pre'];
            $vo['number'] = $vo['number'] == -1 ? '' : $vo['number'];
            $vo['room_ids'] = $vo['room_id'];
            $vo['room_id'] = $WinGoList[$vo['room_id']] ?? '';
        }
        $result = array("total" => $list->total(), "rows" => $list->items());
        return $this->jsonSucc($result);
    }


    /**
     * 获取房间总统计
     */
    public function LatestData()
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
        $room_id = $this->request->get('room_id');
        $lates_win_go = WinList::where('room_id', $room_id)
            ->where('status',0)
            ->order('id asc')
            ->find();
        if (!empty($lates_win_go)) {
            $issue = $lates_win_go['issue'];//最新期数
            $list = collection(WinGoLog::where('room_id', $room_id)
                ->where('issue', $issue)
                ->field('user_id,number,bet')
                ->select())->toArray();
            foreach ($room_lates_data as $k => &$vo) {
                $data = $this->details_statistics($room_id, $k, $list);
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
            'name'=>'系统净输赢',
            'color'=>'#4dbb8c',
        ];
        $new_array[1]['name'] = [
            'name'=>'中奖金额',
            'color'=>'#6bbbee',
        ];
        $new_array[2]['name'] = [
            'name'=>'中奖人数',
            'color'=>'#ce6e58',
        ];
        foreach ($room_lates_data as $key=>$vo){
            $new_array[0][] = $vo['transfer'];
            $new_array[1][] = $vo['win_amount'];
            $new_array[2][] = $vo['win_number'];
        }



        return $this->jsonSucc($new_array);
    }

    /**
     * 房间详细信息
     */
    public function details()
    {
        $room_id = $this->request->get('room_id');
        $number = $this->request->get('number');
        $data = $this->details_statistics($room_id, $number);
        $new_array = [];
        $new_tab = [];
        $new_array[0]['name'] = [
            'name'=>'下注金额',
            'color'=>'#4dbb8c',
        ];
        $new_array[1]['name'] = [
            'name'=>'投注次数',
            'color'=>'#6bbbee',
        ];
        $new_array[2]['name'] = [
            'name'=>'中奖人数',
            'color'=>'#ce6e58',
        ];
        $new_array[3]['name'] = [
            'name'=>'预估返还',
            'color'=>'#efb963',
        ];
        $new_array[4]['name'] = [
            'name'=>'盈亏',
            'color'=>'#4dbb8c',
        ];
        foreach ($data['list'] as $key=>$vo){
            if($key !==13 && $key!==14){
                if(is_int($vo['name'])){
                    $new_tab[] = [
                        'color'=>"#6abcee",
                        'name'=>'数字 '.$vo['name'],
                    ];
                }else{
                    if($vo['name'] == '绿色'){
                        $new_tab[] = [
                            'color'=>"#49bb73",
                            'name'=>$vo['name'],
                        ];
                    }else if($vo['name'] == '红色'){
                        $new_tab[] = [
                            'color'=>"#d86962",
                            'name'=>$vo['name'],
                        ];
                    }else if($vo['name'] == '紫色'){
                        $new_tab[] = [
                            'color'=>"#a9369e",
                            'name'=>$vo['name'],
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
                    else{
                        $new_tab[] = [
                            'color'=>"#6abcee",
                            'name'=>'数字 '.$vo['name'],
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
            'list'=>$new_array,
            'new_tab'=>$new_tab,
            'total_data'=>$data['total_data'],
            'draw_time'=>$data['draw_time'],
            'issue'=>$data['issue'],
            'number_pre'=>$data['number_pre'],
        ];
        return $this->jsonSucc($return);
    }


    /**
     * @param $room_id
     * @param $number
     * @param $list
     * 封装详细信息-》总统计
     */
    public function details_statistics($room_id, $number, $list = [])
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
            'sum_amount' => 0,//总投注
            'win_amount' => 0,//总中奖金额
            'bet_number' => 0,//总下注次数
            'win_number' => 0,//总中奖人数
            'sum_fee' => 0,//总手续费
            'sum_transfer' => 0,//总盈亏
        ];
        $lates_win_go = WinList::where('room_id', $room_id)
            ->where('status',0)
            ->order('id asc')->find();
        if (!empty($lates_win_go)) {
            $issue = $lates_win_go['issue'];//最新期数
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
            $room_rate = RoomBaseConfig::where('room_id', $this->room_id)->value('ext1');
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
            if(empty($lates_win_go['number_pre'])){
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
     * 添加
     */
    public function add()
    {
        $this->error('无权限');
    }

    public function del($ids = null)
    {
        $this->error('无权限');
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }
        $params = $this->request->post();
        if (empty($params)) {
            return $this->jsonFail(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        $winning_numbers = $params['number_pre'];//预设值开奖号码
        $lates_win_go = WinList::where('id', $ids)->find();
        if ($lates_win_go) {
            $RoomData = WinGo::where('id', $lates_win_go['room_id'])->find();
            if (!$RoomData) {
                return $this->jsonFail('房间不存在');
            }
            if ($lates_win_go['draw_time_pre'] - $RoomData['settle_second'] < time()) {
                return $this->jsonFail('时间不在可设定范围');
            }
            if (!($winning_numbers >= 0 && $winning_numbers <= 9)) {
                return $this->jsonFail('预设值开奖号码，不在0-9范围内');
            }
            try {
                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                    $row->validateFailException()->validate($validate);
                }
                $result = $row->allowField(true)->save($params);
                (new WingoOperationLog)->add_data($row['root_id'],$row['issue'],$winning_numbers,$this->auth->id);
                Db::commit();
            } catch (ValidateException|PDOException|\Exception $e) {
                Db::rollback();
                return $this->jsonFail($e->getMessage());
            }
            if (false === $result) {
                return $this->jsonFail(__('No rows were updated'));
            }
        } else {
            return $this->jsonFail('期数不存在');
        }
        return $this->jsonSucc();
    }


    /**
     * 预设值开奖信息
     */
    public function winningSubmit()
    {
        if ($this->request->isPost()) {
            $room_id = $this->request->post('room_id');//房间ID
            $issue = $this->request->post('issue');//期号
            $winning_numbers = $this->request->post('winning_numbers');//预设值开奖号码
            $lates_win_go = WinList::where('room_id', $room_id)->where('issue', $issue)
                ->find();
            if ($lates_win_go) {
                $RoomData = WinGo::where('id', $room_id)->find();
                if (!$RoomData) {
                    return $this->jsonFail('房间不存在');
                }
                if ($lates_win_go['draw_time_pre'] - $RoomData['settle_second'] < time()) {
                    return $this->jsonFail('时间不在可设定范围');
                }
                if (!($winning_numbers >= 0 && $winning_numbers <= 9)) {
                    return $this->jsonFail('预设值开奖号码，不在0-9范围内');
                }

                WinList::where('room_id', $room_id)->where('issue', $issue)->update([
                    'number_pre' => $winning_numbers
                ]);
                (new WingoOperationLog)->add_data($room_id,$issue,$winning_numbers,$this->auth->id);
                return $this->jsonSucc('设置成功');
            } else {
                return $this->jsonFail('期数不存在');
            }
        }
    }


}
