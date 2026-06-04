<?php

namespace app\admin\controller\user;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\admin\model\Room;
use app\common\controller\Backend;
use think\Db;
use app\admin\model\User;

class Details extends Backend
{

    protected $noNeedRight = ['room_name'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new User;
    }


    public function room_name()
    {
        $where = [];
        $new_data = [];
        $primaryvalue = $this->request->request("keyValue");
        $name = $this->request->request("name");
        if(!empty($name)){
            $where['name'] = ['like',"%$name%"];
        }
        if(!empty($primaryvalue)){
            $new_list = Room::where('status','1')->where('id',$primaryvalue)->field('name,id')->find();
            $new_list2 = Room::where('status','1')->where($where)->where('id','neq',$primaryvalue)->field('name,id')->select();
            if($new_list){
                $new_data[] = $new_list;
            }
            foreach ($new_list2 as $vo){
                $new_data[] = $vo;
            }
            $array = ['total'=>count($new_data),'list'=>$new_data];
            return json($array);
        }
        $RoomData = Room::where('status','1')->where($where)->field('name,id')->select();
        $array = ['total'=>count($RoomData),'list'=>$RoomData];
        return json($array);
    }

    /**
     * 查看
     */
    public function index($ids = null)
    {
        $user_info = User::where('id',$ids)->field('platform_id,merchant_id')->find();
        if(!$user_info){
            $this->error('用户不存在');
        }
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2){
            if($this->auth->platform_id != $user_info['platform_id']){
                $this->error('用户不存在');
            }
        }else if($this_admin_access == 3){
            if($this->auth->merchant_id != $user_info['merchant_id']){
                $this->error('用户不存在');
            }
        }

        $room_id = '';
        $RoomData = Room::column('history_table_name','id');
        foreach ($RoomData as $key=>$vo){
            if(!empty($vo)){
                $result = Db::query("SHOW TABLES LIKE '{$vo}'");
                if($result){
                    $room_id = $key;
                }
            }
        }
        $this->assign('room_id',$room_id);
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $filter = $this->request->get("filter", '');
            $filter = (array)json_decode($filter, true);
            $where_array = [];
            $where_array['user_id'] = ['eq',$ids];
            $where_array['platform_id'] = ['eq',$user_info['platform_id']];
            $where_array['merchant_id'] = ['eq',$user_info['merchant_id']];
            $room_table = '';
            foreach ($filter as $key => $value) {
                switch ($key) {
                    case 'sid':
                        $where_array['sid'] = ['eq', $value];
                        break;
                    case 'room_id':
                        if(!empty($RoomData[$value])){
                            $result = Db::query("SHOW TABLES LIKE '{$RoomData[$value]}'");
                            if($result){
                                $room_table = $RoomData[$value];
                                $room_id = $value;
                            }else{
                                return json(["total" => 0, "rows" => []]);
                            }
                        }else{
                            return json(["total" => 0, "rows" => []]);
                        }
                        break;
                    case 'bet':
                        $value = str_replace(' - ', ',', $value);
                        $arr = array_slice(explode(',', $value), 0, 2);
                        $where_array['bet'] = ['BETWEEN', [$arr[0] * 1000, $arr[1] * 1000]];
                        break;
                    case 'transfer':
                        $value = str_replace(' - ', ',', $value);
                        $arr = array_slice(explode(',', $value), 0, 2);
                        $where_array['transfer'] = ['BETWEEN', [$arr[0] * 1000, $arr[1] * 1000]];
                        break;
                    case 'created_at':
                        $value = str_replace(' - ', ',', $value);
                        $arr = array_slice(explode(',', $value), 0, 2);
                        $where_array['created_at'] = ['BETWEEN', [strtotime($arr[0])*1000, strtotime($arr[1])*1000]];
                        break;
                }
            }
            if(empty($room_table)){
                foreach ($RoomData as $key=>$vo){
                    if(!empty($vo)){
                        $result = Db::query("SHOW TABLES LIKE '{$vo}'");
                        if($result){
                            $room_table = $vo;
                            $room_id = $key;
                        }
                    }
                }
            }
            $RoomData = Room::column('id,name');
            $list = $this->model
                    ->table($room_table)
                    ->where($where_array)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as &$row) {
                $row['room_id'] = $RoomData[$room_id];
                $row['bet'] = FormatMoney($row['bet']);
                $row['transfer'] = FormatMoney($row['transfer']);
                $row['created_at'] = bcdiv($row['created_at'],1000);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
       $created_atdefaultValue = date('Y-m-d 00:00:00') .' - ' . date('Y-m-d 23:59:59');
        $this->assign('created_atdefaultValue',$created_atdefaultValue);
        $this->assign('room_id',$room_id);
        return $this->view->fetch();
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
