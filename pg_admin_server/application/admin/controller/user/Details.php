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
        $ids = $this->request->request("user_id");
        $user_info = User::where('id',$ids)->field('platform_id,merchant_id')->find();
        if(!$user_info){
            return $this->jsonFail('用户不存在');
        }
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2){
            if($this->auth->platform_id != $user_info['platform_id']){
                return $this->jsonFail('用户不存在');
            }
        }else if($this_admin_access == 3){
            if($this->auth->merchant_id != $user_info['merchant_id']){
                return $this->jsonFail('用户不存在');
            }
        }

        $room_id = '';
        $RoomData = Room::column('history_table_name,name,type_id','id');
        foreach ($RoomData as $key=>$vo){
            if(!empty($vo['history_table_name'])){
                $result = Db::query("SHOW TABLES LIKE '{$vo['history_table_name']}'");
                if($result){
                    $room_id = $key;
                }
            }
        }
        
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $filter = $this->request->get("filter", '');
        $params = $this->request->get();
        $room_id = $params['room_id'] ?? '';

        $filter = (array)json_decode($filter, true);
        if ($room_id) {
            $filter['room_id'] = $room_id;
        }
        $where_array = [];
        $where_array['user_id'] = ['eq',$ids];
        $where_array['platform_id'] = ['eq',$user_info['platform_id']];
        $where_array['merchant_id'] = ['eq',$user_info['merchant_id']];

        if (!empty($params['created_at'])) {
            $dates = $params['created_at'];
            if (is_array($dates) && count($dates) >= 2) {
                $start = strtotime($dates[0]) * 1000;
                $end = strlen($dates[1]) > 10 ? strtotime($dates[1]) * 1000 : (strtotime($dates[1]) + 86400) * 1000;
                $where_array['created_at'] = [['>=', $start], ['<', $end]];
            }
        }

        $room_table = '';
        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'sid':
                    $where_array['sid'] = ['eq', $value];
                    break;
                case 'room_id':
                    if(!empty($RoomData[$value]['history_table_name'])){
                        $result = Db::query("SHOW TABLES LIKE '{$RoomData[$value]['history_table_name']}'");
                        if($result){
                            $room_table = $RoomData[$value];
                            $room_id = $value;
                        }else{
                            return $this->jsonFail('无游戏记录');
                        }
                    }else{
                        return $this->jsonFail('无游戏记录');
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
        if(!$room_id && empty($room_table)){
            foreach ($RoomData as $key=>$vo){
                if(!empty($vo['history_table_name'])){
                    $result = Db::query("SHOW TABLES LIKE '{$vo['history_table_name']}'");
                    if($result){
                        $room_table = $vo['history_table_name'];
                        $room_id = $key;
                    }
                }
            }
        }
        // $RoomData = Room::column('id,name');
        $roomName = [];
        $gameTypes = $this->getGameTypes();
        foreach ($RoomData as $key => $val) {
            $roomName[$key] = sprintf('%s(%s)', $val['name'], $gameTypes[$val['type_id']] ?? '-');
        }
        $list = $this->model
                ->table($room_table)
                ->where($where_array)
                ->order($sort, $order)
                ->paginate($limit);
        foreach ($list as &$row) {
            $row['room_id'] = $roomName[$room_id];
            $row['bet'] = FormatMoney($row['bet']);
            $row['transfer'] = FormatMoney($row['transfer']);
            $row['created_at'] = bcdiv($row['created_at'],1000);
        }
        $result = [
            "total" => $list->total(), 
            "rows" => $list->items(), 
            'room_id' => $room_id,
            'room_name' => $roomName,
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
