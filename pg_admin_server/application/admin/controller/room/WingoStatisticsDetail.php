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

class WingoStatisticsDetail extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['merchant_name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WinGoLog();
    }

    public function merchant_name()
    {
        $where = [];
        $new_data = [];
        $primaryvalue = $this->request->request("keyValue");
        if(!empty($primaryvalue)){
            $new_list = Merchants::where('id',$primaryvalue)->field('name,id')->find();
            $new_list2 = Merchants::where('id','neq',$primaryvalue)->field('name,id')->select();
            $new_data[] = $new_list;
            foreach ($new_list2 as $vo){
                $new_data[] = $vo;
            }
            $array = ['total'=>count($new_data),'list'=>$new_data];
            return json($array);
        }
        $RoomData = Merchants::where($where)->field('name,id')->select();
        $array = ['total'=>count($RoomData),'list'=>$RoomData];
        return json($array);
    }

    /**
     * 查看
     */
    public function index()
    {
        $issue = $this->request->get('issue');
        $room_id = $this->request->get('room_id');
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        $filter = $this->request->get("filter", '');
        $filter = (array)json_decode($filter, true);
        $search = [];
        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'merchant_id':
                    $search['merchant_id'] = ['eq', $value];
                    break;
            }
        }



        $MerchantArray = Merchants::column('name','id');
        $PlatformArray = Platform::column('name','id');

        $list = collection($this->model
            ->where($search)
            ->where('room_id',$room_id)
            ->where('issue',$issue)
            ->group('platform_id,merchant_id')
            ->field('platform_id,merchant_id,count(*) count,count(distinct user_id) user_number,sum(bet) bet,sum(transfer) transfer')
            ->select())->toArray();


        foreach ($list as &$vo){
            $vo['platform_id'] = $PlatformArray[$vo['platform_id']]??'';
            $vo['merchant_id'] = $MerchantArray[$vo['merchant_id']]??'';
            $vo['bet'] = FormatMoney($vo['bet']);
            $vo['transfer'] = FormatMoney($vo['transfer']);

        }

        $result = ['total' => count($list), 'rows' => $list];
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
