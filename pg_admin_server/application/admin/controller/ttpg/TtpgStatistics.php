<?php

namespace app\admin\controller\ttpg;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\admin\model\Room;
use app\common\controller\Backend;

class TtpgStatistics extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['platform_name','merchant_name'];
    protected $type_id = 2;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\TtpgDailyStatistics;
    }


    public function platform_name()
    {
        $platform_data = Platform::column('id,name');
        return json($platform_data);
    }
    public function merchant_name()
    {
        $platform_data = Merchants::column('id,name');
        return json($platform_data);
    }

    /**
     * 查看
     */
    public function index()
    {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams(['date_time' => 'dates']);

        $current_permission = 2;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        $field = "date_time,merchant_id,room_id,platform_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times,sum(user_number) user_number";
        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $group = 'date_time,merchant_id,room_id';
        $RoomArray = Room::column('name','id');
        $where_array = [];
        if($this_admin_access == 2){
            $where_array['platform_id'] = $this->auth->platform_id;
        }else if($this_admin_access == 3){
            $where_array['merchant_id'] = $this->auth->merchant_id;
        }else{
            $group = 'date_time,room_id';
            $current_permission = 1;
        }


        $list = $this->model
            ->where($where)
            ->where($where_array)
            ->group($group)
            ->field($field)
            ->order($sort, $order)
            ->paginate($limit);

        $data = [];
        $total = [
            'date_time'=>'合计',
            'merchant_id'=>'',
            'bet_amount'=>0,
            'win_amount'=>0,
            'transfer'=>0,
            'win_or_loss'=>0,
            'bet_times'=>0,
            'user_number'=>0,
        ];

        foreach ($list as &$vo){
            $supplier_data = [];
            $supplier_data['bet_amount'] = FormatMoney($vo['bet_amount']);
            $supplier_data['win_amount'] = FormatMoney($vo['win_amount']);
            $supplier_data['transfer'] = FormatMoney($vo['transfer']);
            $supplier_data['win_or_loss'] = bcsub($supplier_data['bet_amount'],$supplier_data['win_amount'],2);
            $supplier_data['date_time'] = $vo['date_time'];
            $supplier_data['merchant_id'] = $MerchantArray[$vo['merchant_id']]??'';
            $supplier_data['platform_id'] = $PlatformArray[$vo['platform_id']]??'';
            $supplier_data['bet_times'] = $vo['bet_times'];
            $supplier_data['user_number'] = $vo['user_number'];
            $supplier_data['room_id'] = $RoomArray[$vo['room_id']]??'';


            $total['bet_times'] = bcadd($total['bet_times'], $vo['bet_times']);
            $total['user_number'] = bcadd($total['user_number'], $vo['user_number']);
            $total['bet_amount'] = bcadd($total['bet_amount'], FormatMoney($vo['bet_amount']), 2);
            $total['win_amount'] = bcadd($total['win_amount'], FormatMoney($vo['win_amount']), 2);
            $total['transfer'] = bcadd($total['transfer'], FormatMoney($vo['transfer']), 2);
            $total['win_or_loss'] = bcadd($total['win_or_loss'], $supplier_data['win_or_loss'], 2);
            $data[] = $supplier_data;
        }
        $data[] = $total;
        $result = [
            'total' => $list->total(), 
            'rows' => $data,
            'current_permission'=>$current_permission,
            'merchant_name' => $MerchantArray,
            'room_name' => $RoomArray,
        ];
        return $this->jsonSucc($result);
    }




    public function room_name()
    {
        $where = [];
        $post = $this->request->post('name');
        if(!empty($post)){
            $where['name'] = ['like',"{$post}%"];
        }
        $RoomData = Room::where('type_id',$this->type_id)->where($where)->field('name,id')->select();
        $array = ['total'=>count($RoomData),'list'=>$RoomData];
        return json($array);
    }



    public function add()
    {
        $this->error('无权限');
    }
    public function edit($ids = NULL)
    {
        $this->error('无权限');
    }
    public function del($ids = NULL)
    {
        $this->error('无权限');
    }
}
