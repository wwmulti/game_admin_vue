<?php

namespace app\admin\controller\room;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\admin\model\WinGo;
use app\common\model\database1\User as UserModel;
use app\common\controller\Backend;
use think\exception\DbException;
use think\response\Json;

class WingoStatistics extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['wingo_room','platform_name','merchant_name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UserWingoDailyStatistics;
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

    public function wingo_room()
    {
        $list = WinGo::where('status', 1)->order('weight desc')->field('id,seconds')->select();
        $new_data = [];
        foreach ($list as &$vo) {
            $seconds = $vo['seconds'];
            $vo['formatted_time'] = SetTime($seconds);
            $new_data[$vo['id']] = $vo['formatted_time'];
        }
        return json($new_data);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        $current_permission = 2;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        $field = "room_id,date_time,merchant_id,platform_id,sum(bet_amount) bet_amount,sum(win_amount) win_amount,sum(transfer) transfer,sum(bet_times) bet_times,sum(user_number) user_number";
        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');
        $group = 'date_time,merchant_id';
        $where_array = [];
        if($this_admin_access == 2){
            $where_array['platform_id'] = $this->auth->platform_id;
        }else if($this_admin_access == 3){
            $where_array['merchant_id'] = $this->auth->merchant_id;
        }else{
            $group = 'date_time';
            $current_permission = 1;
        }

        $listWinGo = WinGo::where('status', 1)->order('weight desc')->field('id,seconds')->select();
        $NewListWinGo = [];
        foreach ($listWinGo as &$vo) {
            $seconds = $vo['seconds'];
            $vo['formatted_time'] = SetTime($seconds);
            $NewListWinGo[$vo['id']] = $vo['formatted_time'];
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
        $data[] = $total;
        $result = ['total' => $list->total(), 'rows' => $data,'current_permission'=>$current_permission];
        return json($result);
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
