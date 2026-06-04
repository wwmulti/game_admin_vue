<?php

namespace app\adminapi\controller;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\admin\model\Admin as AdminModel;
use app\common\model\database1\User as UserModel;
use app\admin\model\Room;
use app\admin\model\WinGo;

class Statistics extends MainController
{
    public $merchantId;
    public $platformId;

    public function __construct()
    {
        parent::__construct();

        $info  = AdminModel::where(['id' => $this->adminId])->field('platform_id,merchant_id')->find();

        $this->merchantId = $info['merchant_id'];
        $this->platformId = $info['platform_id'];
    }
    /**
     *  商户数据
     */
    public function supplier()
    {
        [$page, $limit] = $this->getPageValue();

        list($startDate, $endDate) = request()->get('createTime/a');
        $merchantId = request()->get('merchantId', 0, 'intval');
        $condition = [];        
        if (!empty($startDate) && !empty($endDate)) {
            $condition['date_time'] = [
                ['>=', date('Y-m-d', strtotime($startDate))],
                ['<', date('Y-m-d', strtotime('+1days', strtotime($endDate)))]
            ];        
        }
        if (!empty($merchantId)) {
            $condition['merchant_id'] = $merchantId;
        }


        $groupId = AuthGroupAccess::where('uid', $this->adminId)->value('group_id');
        $field = "date_time,merchant_id,platform_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times,sum(activeUserNum) activeUserNum,sum(newUserNum) newUserNum";

        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $group = 'date_time,merchant_id';

        $currentPermission = 2;
        if ($groupId == 2) {
            $condition['platform_id'] = $this->platformId;
        } else if ($groupId == 3) {
            $condition['merchant_id'] =  $this->merchantId;
        } else {
           $group = 'date_time';
           $currentPermission = 1; 
        }
        
        $model = new \app\common\model\database1\SupplierDailyStatistics;
        $list = $model
            ->where($condition)
            ->group($group)
            ->field($field)
            ->order('date_time desc')
            ->page($page)->limit($limit)->select();

        $data = [];
        $total = [
            'date_time'=>'合计',
            'merchant_id'=>'',
            'activeUserNum'=>0,
            'newUserNum'=>0,
            'bet_amount'=>0,
            'win_amount'=>0,
            'transfer'=>0,
            'win_or_loss'=>0,
            'bet_times'=>0,
        ];

        foreach ($list as &$vo){
            $supplier_data = [];
            $supplier_data['bet_amount'] = FormatMoney($vo['bet_amount']);
            $supplier_data['win_amount'] = FormatMoney($vo['win_amount']);
            $supplier_data['transfer'] = FormatMoney($vo['transfer']);
            $supplier_data['win_or_loss'] = bcsub($supplier_data['bet_amount'],$supplier_data['win_amount'],2);
            $supplier_data['newUserNum'] = $vo['newUserNum'];
            $supplier_data['activeUserNum'] = $vo['activeUserNum'];
            $supplier_data['date_time'] = $vo['date_time'];
            $supplier_data['merchant_id'] = $MerchantArray[$vo['merchant_id']]??'';
            $supplier_data['platform_id'] = $PlatformArray[$vo['platform_id']]??'';
            $supplier_data['bet_times'] = $vo['bet_times'];

            $total['bet_times'] = bcadd($total['bet_times'], $vo['bet_times']);
            $total['activeUserNum'] = bcadd($total['activeUserNum'], $vo['activeUserNum']);
            $total['newUserNum'] = bcadd($total['newUserNum'], $vo['newUserNum']);
            $total['bet_amount'] = bcadd($total['bet_amount'], FormatMoney($vo['bet_amount']), 2);
            $total['win_amount'] = bcadd($total['win_amount'], FormatMoney($vo['win_amount']), 2);
            $total['transfer'] = bcadd($total['transfer'], FormatMoney($vo['transfer']), 2);
            $total['win_or_loss'] = bcadd($total['win_or_loss'], $supplier_data['win_or_loss'], 2);
            $data[] = $supplier_data;
        }
        $data[] = $total;

        $count = $model
            ->where($condition)
            ->group($group)->count();
        $result = [
            'total' => $count,
            'list' => $data,
            'currentPermission' => $currentPermission
        ];  
        return $this->jsonSucc($result);   
    }

    /**
     *  用户数据
     */
    public function user()
    {
        [$page, $limit] = $this->getPageValue();

        list($startDate, $endDate) = request()->get('createTime/a');
        $merchantId = request()->get('merchantId', 0, 'intval');
        $condition = [];        
        if (!empty($startDate) && !empty($endDate)) {
            $condition['date_time'] = [
                ['>=', date('Y-m-d', strtotime($startDate))],
                ['<', date('Y-m-d', strtotime('+1days', strtotime($endDate)))]
            ];        
        }
        if (!empty($merchantId)) {
            $condition['merchant_id'] = $merchantId;
        }

        $currentPermission = 2;
        $groupId = AuthGroupAccess::where('uid', $this->adminId)->value('group_id');
        $field = "date_time,merchant_id,platform_id,user_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times";
        $group = 'date_time,merchant_id,user_id';
        
        if($groupId == 2){
            $condition['platform_id'] =  $this->platformId;
        }else if($groupId == 3){
            $condition['merchant_id'] = $this->merchantId;
        }else{
            $group = 'date_time,user_id';
            $currentPermission = 1;
        }

        $model = new \app\common\model\database1\UserDailyStatistics;

        $list = $model
            ->where($condition)
            ->group($group)
            ->field($field)
            ->order('date_time desc')
            ->page($page)->limit($limit)->select();

        $data = [];
        $total = [
            'date_time'=>'合计',
            'merchant_id'=>'',
            'bet_amount'=>0,
            'win_amount'=>0,
            'transfer'=>0,
            'win_or_loss'=>0,
            'bet_times'=>0,
        ];
        $user_ids = [];
        foreach ($list as $ids){
            $user_ids[] = $ids['user_id'];
        }

        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $UserArray = UserModel::where('id','in',$user_ids)->column('name','id');


        foreach ($list as &$vo){
            $supplier_data = [];
            $supplier_data['bet_amount'] = FormatMoney($vo['bet_amount']);
            $supplier_data['win_amount'] = FormatMoney($vo['win_amount']);
            $supplier_data['transfer'] = FormatMoney($vo['transfer']);
            $supplier_data['win_or_loss'] = bcsub($supplier_data['bet_amount'],$supplier_data['win_amount'],2);
            $supplier_data['date_time'] = $vo['date_time'];
            $supplier_data['merchant_id'] = $MerchantArray[$vo['merchant_id']]??'';
            $supplier_data['platform_id'] = $PlatformArray[$vo['platform_id']]??'';
            $supplier_data['user_id'] = $UserArray[$vo['user_id']]??'';
            $supplier_data['bet_times'] = $vo['bet_times'];
            $total['bet_times'] = bcadd($total['bet_times'], $vo['bet_times']);
            $total['bet_amount'] = bcadd($total['bet_amount'], FormatMoney($vo['bet_amount']), 2);
            $total['win_amount'] = bcadd($total['win_amount'], FormatMoney($vo['win_amount']), 2);
            $total['transfer'] = bcadd($total['transfer'], FormatMoney($vo['transfer']), 2);
            $total['win_or_loss'] = bcadd($total['win_or_loss'], $supplier_data['win_or_loss'], 2);
            $data[] = $supplier_data;
        }
        $data[] = $total;

        $count = $model
            ->where($condition)
            ->group($group)->count();
        $result = ['total' => $count, 'list' => $data,'currentPermission'=> $currentPermission];

        return $this->jsonSucc($result);
    }

    /**
     * ttpg统计
     */
    public function ttpg()
    {
        [$page, $limit] = $this->getPageValue();

        $currentPermission = 2;        
        $groupId = AuthGroupAccess::where('uid', $this->adminId)->value('group_id');

        $field = "date_time,merchant_id,room_id,platform_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times,sum(user_number) user_number";
        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $group = 'date_time,merchant_id,room_id';
        $RoomArray = Room::column('name','id');



        $roomId = request()->get('roomId', 0, 'intval');
        $merchantId = request()->get('merchantId', 0, 'intval');
        list($startDate, $endDate) = request()->get('createTime/a');

        $condition = [];        
        if (!empty($startDate) && !empty($endDate)) {
            $condition['date_time'] = [
                ['>=', date('Y-m-d', strtotime($startDate))],
                ['<', date('Y-m-d', strtotime('+1days', strtotime($endDate)))]
            ];        
        }
        if (!empty($merchantId)) {
            $condition['merchant_id'] = $merchantId;
        }
        if (!empty($roomId)) {
            $condition['room_id'] = $roomId;
        }

        if($groupId == 2){
            $condition['platform_id'] = $this->platformId;;
        }else if($groupId == 3){
            $condition['merchant_id'] = $this->merchantId;;
        }else{
            $group = 'date_time,room_id';
            $currentPermission = 1;
        }

        $model = new \app\admin\model\TtpgDailyStatistics;
        $list = $model
            ->where($condition)
            ->group($group)
            ->field($field)
            ->order('date_time desc')
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


        $count = $model
            ->where($condition)
            ->group($group)->count();
        $result = ['total' => $count, 'list' => $data,'currentPermission'=> $currentPermission];

        return $this->jsonSucc($result);
    }

    /**
     * wingo统计
     */
    public function wingo()
    {        
        [$page, $limit] = $this->getPageValue();

        $roomId = request()->get('roomId', 0, 'intval');
        $merchantId = request()->get('merchantId', 0, 'intval');
        list($startDate, $endDate) = request()->get('createTime/a');

        $condition = [];        
        if (!empty($startDate) && !empty($endDate)) {
            $condition['date_time'] = [
                ['>=', date('Y-m-d', strtotime($startDate))],
                ['<', date('Y-m-d', strtotime('+1days', strtotime($endDate)))]
            ];        
        }
        if (!empty($merchantId)) {
            $condition['merchant_id'] = $merchantId;
        }
        if (!empty($roomId)) {
            $condition['room_id'] = $roomId;
        }


        $currentPermission = 2;
        $groupId = AuthGroupAccess::where('uid', $this->adminId)->value('group_id');
        $field = "room_id,date_time,merchant_id,platform_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times,sum(user_number) user_number";

        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $group = 'date_time,merchant_id';

        if($groupId == 2){
            $condition['platform_id'] = $this->platformId;
        }else if($groupId == 3){
            $condition['merchant_id'] = $this->merchantId;
        }else{
            $group = 'date_time';
            $currentPermission = 1;
        }

        $listWinGo = WinGo::where('status', 1)->order('weight desc')->field('id,seconds')->select();
        $NewListWinGo = $roomList = [];
        foreach ($listWinGo as &$vo) {
            $seconds = $vo['seconds'];
            $vo['formatted_time'] = SetTime($seconds);
            $NewListWinGo[$vo['id']] = $vo['formatted_time'];
            $roomList[] = ['id' => $vo['id'], 'name' => $vo['formatted_time']];
        }

        $model = new \app\admin\model\UserWingoDailyStatistics;

        $list = $model
            ->where($condition)
            ->group($group)
            ->field($field)
            ->order('date_time desc')
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
            $supplier_data['room_id'] = $NewListWinGo[$vo['room_id']]??'';
            $supplier_data['bet_amount'] = FormatMoney($vo['bet_amount']);
            $supplier_data['win_amount'] = FormatMoney($vo['win_amount']);
            $supplier_data['transfer'] = FormatMoney($vo['transfer']);
            $supplier_data['win_or_loss'] = bcsub($supplier_data['bet_amount'],$supplier_data['win_amount'],2);
            $supplier_data['date_time'] = $vo['date_time'];
            $supplier_data['merchant_id'] = $MerchantArray[$vo['merchant_id']]??'';
            $supplier_data['platform_id'] = $PlatformArray[$vo['platform_id']]??'';
            $supplier_data['bet_times'] = $vo['bet_times'];
            $supplier_data['user_number'] = $vo['user_number'];

            $total['bet_times'] = bcadd($total['bet_times'], $vo['bet_times']);
            $total['user_number'] = bcadd($total['user_number'], $vo['user_number']);
            $total['bet_amount'] = bcadd($total['bet_amount'], FormatMoney($vo['bet_amount']), 2);
            $total['win_amount'] = bcadd($total['win_amount'], FormatMoney($vo['win_amount']), 2);
            $total['transfer'] = bcadd($total['transfer'], FormatMoney($vo['transfer']), 2);
            $total['win_or_loss'] = bcadd($total['win_or_loss'], $supplier_data['win_or_loss'], 2);
            $data[] = $supplier_data;
        }
 
        $count = $model
            ->where($condition)
            ->group($group)->count();
        $result = ['total' => $count, 'list' => $data,'currentPermission'=> $currentPermission, 'roomList' => $roomList];

        return $this->jsonSucc($result);
    }
}
