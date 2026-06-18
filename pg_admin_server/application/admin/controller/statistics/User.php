<?php

namespace app\admin\controller\statistics;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\common\model\database1\User as UserModel;
use app\common\controller\Backend;
use think\exception\DbException;
use think\response\Json;

class User extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['platform_name','merchant_name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\database1\UserDailyStatistics;
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
        $sort = 'date_time';

        $current_permission = 2;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        $field = "date_time,merchant_id,platform_id,user_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times";
        $group = 'date_time,merchant_id,user_id';
        $where_array = [];
        if($this_admin_access == 2){
            $where_array['platform_id'] = $this->auth->platform_id;
        }else if($this_admin_access == 3){
            $where_array['merchant_id'] = $this->auth->merchant_id;
        }else{
            $group = 'date_time,user_id';
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
            $supplier_data['user_id'] = $UserArray[$vo['user_id']] ?? sprintf('user-%s',$vo['user_id']);
            $supplier_data['bet_times'] = $vo['bet_times'];
            $total['bet_times'] = bcadd($total['bet_times'], $vo['bet_times']);
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
            'current_permission' => $current_permission,
            'merchant_name' => Merchants::column('name', 'id'),
        ];
        return $this->jsonSucc($result);
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
