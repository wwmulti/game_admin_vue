<?php

namespace app\admin\controller\merchant;


use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\BetGroupConfig;
use app\admin\model\Room;
use app\admin\model\RoomGroupConfig;
use app\common\controller\Backend;
use app\admin\model\merchant\Platform;
use fast\Random;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Session;
use think\Validate;
use app\admin\model\BetAmountConfig;
use app\admin\model\merchant\Setting;

/**
 * 管理员管理
 *
 * @icon fa fa-circle-o
 */
class Merchant extends Backend
{

    /**
     * Merchant模型对象
     * @var \app\admin\model\merchant\Merchants
     */
    protected $model = null;
    protected $platformData = null;
    protected $multiFields = 'status,recommend_switch,game_switch,popular_switch,is_popular,is_blogger,vip_switch,invite_count_reward,is_open_coding';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\merchant\Merchants;
        $rtp_list = \app\admin\model\Rtp::select();
        $this->view->assign('rtp_list', $rtp_list);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        $platform_id = $this->request->get('platform_id','');
        list($where, $sort, $order, $offset, $limit) = $this->buildparams(['name' => 'like'], ['platform_id']);
        $where_array = [];
        $current_permission = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        $field = "id,admin_id,platform_id,token,secret,name,mark,rtp_id,cash_get_url,cash_transferinout_url,style_name_switch,style_bb_switch,style_ec_switch,style_oj_switch,status,free_spin_switch,auto_max,ip_array,official_currency";
        if($this_admin_access == 2){
            $where_array['platform_id'] = $this->auth->platform_id;
            $current_permission = 2;
        }else if($this_admin_access == 3){
            $where_array['platform_id'] = $this->auth->platform_id;
            $where_array['id'] = $this->auth->merchant_id;
            $current_permission = 2;
        }else{
            if(!empty($platform_id)){
                $where_array['platform_id'] = $platform_id;
            }
        }
        $list = $this->model
            ->where($where)
            ->where($where_array)
            ->field($field)
            ->order($sort, $order)
            ->paginate($limit);
        $rtp_list = \app\admin\model\Rtp::field('id,rtp')->select();

        // 批量获取所有商户的 conf 配置（id => conf）
        $confMap = [];
        if ($list->count()) {
            $ids = array_column($list->items(), 'id');
            $confMap = Setting::where('id', 'in', $ids)->column('conf', 'id');
        }
        $defaultConf = ['max_reward' => 0];

        foreach ($list as &$vo){
            // 注入 setting 字段
            $conf = isset($confMap[$vo['id']]) ? json_decode($confMap[$vo['id']], true) : $defaultConf;
            $vo['setting'] = $conf;
            $vo['username'] = '';
            $vo['nickname'] = '';
            if(!empty($vo['admin_id'])){
                $admin_info = Admin::where('id',$vo['admin_id'])->find();
                $vo['username'] = $admin_info['username']??'';
                $vo['nickname'] = $admin_info['nickname']??'';
            }
            unset($vo['admin_id']);
            if($current_permission == 2){
                unset($vo['mark']);
            }
        }

        $platform_data = Platform::column('id,name');
        $result = [
            'total' => $list->total(), 
            'rows' => $list->items(), 
            'current_permission' => $current_permission, 
            'rtpList' => $rtp_list,
            'platform_data' => $platform_data,
        ];
        return $this->jsonSucc($result);
    }


    /**
     * 添加
     */
    public function add()
    {
        /* if (false === $this->request->isAjax()) {

            $room_data = Room::where('reward_status',1)->field('id,name')->select();
            foreach ($room_data as &$vo){
                $RoomGroupConfig = RoomGroupConfig::where('room_id',$vo['id'])->field('id,name')->order('id desc')->select();
                $vo['config'] =$RoomGroupConfig;
            }
            $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');

            $platform_id = '';
            $current_permission = 1;
            if($this_admin_access == 2){
                $platform_id = $this->auth->platform_id;
                $current_permission = 2;
            }else if($this_admin_access == 3){
                $platform_id = $this->auth->platform_id;
                $current_permission = 2;
            }
            $this->view->assign('platform_id', $platform_id);
            $this->view->assign('current_permission', $current_permission);
            $platform_data = Platform::column('id,name');
            $this->view->assign('platformData', $platform_data);
            $this->view->assign('room_data', $room_data);
            return $this->view->fetch();
        } */
        $params = $this->request->post();
        if (empty($params)) {
            return $this->jsonFail(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        // 判断账号是否存在
        $this_admin = Admin::where('username', $params['username'])->find();
        if ($this_admin) {
            return $this->jsonFail('账号已存在');
        }
        Db::startTrans();
        try {
            $params = $this->request->post();
            $add_data = [
                'token' => $params['token'],
                'secret' => $params['secret'],
                'name' => $params['name'],
                'rtp_id' => $params['rtp_id'],
                'cash_get_url' => $params['cash_get_url'],
                'cash_transferinout_url' => $params['cash_transferinout_url'],
                // 'mark' => $params['mark'],
                'style_name_switch' => $params['style_name_switch'],
                'style_bb_switch' => $params['style_bb_switch'],
                'style_ec_switch' => $params['style_ec_switch'],
                'style_oj_switch' => $params['style_oj_switch'],
                'type' => 0,
                'status' => $params['status'],
                'created_at' => date('Y-m-d H:i:s'),
                'platform_id' => $params['platform_id'],
                'free_spin_switch' => $params['free_spin_switch'],
                'auto_max' => $params['auto_max'],
                // 'm_path' => $params['m_path'],
                // 'static_domain' => $params['static_domain'],
            ];
            $merchants_id = $this->model->insertGetId($add_data);
            $salt = Random::alnum();
            $admin_data = [
                'username' => $params['username'],
                'nickname' => $params['name'],
                'salt' => $salt,
                'password' => $this->auth->getEncryptPassword($params['password'], $salt),
                'avatar' => '/assets/img/avatar.png',
                'platform_id' => $params['platform_id'],
                'merchant_id' => $merchants_id,
            ];
            $admin_id = Admin::insertGetId($admin_data);
            AuthGroupAccess::insert([
                'uid' => $admin_id,
                'group_id' => 3,
            ]);
            $this->model->where('id',$merchants_id)->update(['admin_id'=>$admin_id]);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        }
        return $this->jsonSucc();
    }


    public function room_config_add($id,$data)
    {
        $new_room_array = [];
        foreach ($data as $vo){
            $this_array = explode(':',$vo);
            $new_room_array[] = [
                'room_id'=>$this_array[0],
                'group_id'=>$this_array[1],
            ];
        }
        $add_data = [];
        foreach ($new_room_array as $vo){
            if(!empty($vo['group_id'])){
                $group_ids = RoomGroupConfig::where('room_id',$vo['room_id'])->where('id',$vo['group_id'])->value('group_ids');
            }else{
                $group_ids = RoomGroupConfig::where('room_id',$vo['room_id'])->value('group_ids');
            }
            $config_data = BetAmountConfig::where('room_id',$vo['room_id'])->select();
            foreach ($config_data as $vv){
                $add_data[] = [
                    'room_id'=>$vo['room_id'],
                    'group_id'=>$group_ids,
                    'bet_amount'=>$vv['bet_amount'],
                    'merchant_id'=>$id,
                    'reward_id'=>$vo['group_id'],
                ];
            }
        }
        BetGroupConfig::insertAll($add_data);
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $params = $this->request->post();
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $ids = $params['id'];

        $AuthGroupAccessStatus = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2){
            $AuthGroupAccessStatus = 2;
            $row = $this->model->where('id',$ids)->where('platform_id',$this->auth->platform_id)->find();
        }else if($this_admin_access == 3){
            $AuthGroupAccessStatus = 2;
            $row = $this->model
                ->where('id',$ids)
                ->where('platform_id',$this->auth->platform_id)
                ->where('id',$this->auth->merchant_id)
                ->find();

        }else{
            $row = $this->model->where('id',$ids)->find();
        }
        if (!$row) {
           return $this->jsonFail(__('No Results were found')); 
        }
        $admin_select_info = Admin::where('id', $row['admin_id'])->find();
        if(!$admin_select_info){
            $row['admin_id'] = '';
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }
        /* if (false === $this->request->isPost()) {

            $room_data = Room::where('reward_status',1)->field('id,name')->select();
            $row['username'] = '';
            if(!empty($row['admin_id'])){
                $admin_info = Admin::where('id', $row['admin_id'])->field('id,username')->find();
                $row['username'] = $admin_info['username']??'';
            }
            $this->view->assign('AuthGroupAccessStatus', $AuthGroupAccessStatus);
            $this->view->assign('row', $row);
            $this->view->assign('room_data', $room_data);
            $this->view->assign('merchant_id', $row['id']);
            return $this->view->fetch();
        } */

        
        //是否采用模型验证
        if ($this->modelValidate) {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
            $row->validateFailException()->validate($validate);
        }


        $admin_data = [];
        if (!empty($params['password'])) {
            if (!Validate::is($params['password'], '\S{6,16}')) {
                return $this->jsonFail('密码长度必须在6-30位之间，不能包含空格"');
            }
            $admin_data['salt'] = Random::alnum();
            $admin_data['password'] = $this->auth->getEncryptPassword($params['password'], $admin_data['salt']);
        }
        $update = [];
        if($AuthGroupAccessStatus == 1){
            // 判断账号是否存在
            if(!empty($row['admin_id'])){
                $this_admin_where['id'] = ['neq', $row['admin_id']];
                $this_admin_where['username'] = ['eq', $params['username']];
                $this_admin = Admin::where($this_admin_where)->find();
                if ($this_admin) {
                    return $this->jsonFail('账号已存在');
                }
            }else{
                $this_admin_where['username'] = ['eq', $params['username']];
                $this_admin = Admin::where('username',$params['username'])->find();
                if ($this_admin) {
                    return $this->jsonFail('账号已存在');
                }
            }
            $update['name'] = $params['name'];
            $update['token'] = $params['token'];
            $update['secret'] = $params['secret'];
            $update['mark'] = $params['mark'];
            $update['status'] = $params['status'];
            $admin_data['username'] = $params['username'];
            $admin_data['nickname'] = $params['name'];
            // $update['m_path'] = $params['m_path'];
            // $update['static_domain'] = $params['static_domain'];
        }

        $update['free_spin_switch'] = $params['free_spin_switch'];
        $update['auto_max'] = $params['auto_max'];
        $update['rtp_id'] = $params['rtp_id'];
        $update['cash_get_url'] = $params['cash_get_url'];
        $update['cash_transferinout_url'] = $params['cash_transferinout_url'];
        $update['style_name_switch'] = $params['style_name_switch'];
        $update['style_bb_switch'] = $params['style_bb_switch'];
        $update['style_ec_switch'] = $params['style_ec_switch'];
        $update['style_oj_switch'] = $params['style_oj_switch'];
        $update['ip_array'] = $params['ip_array'];
        $update['official_currency'] = $params['official_currency'];
        $update['updated_at'] = date('Y-m-d H:i:s');
 
        Db::startTrans();
        try {
            if(!empty($row['admin_id'])){
                if(!empty($admin_data)){
                    Admin::where('id', $row['admin_id'])->update($admin_data);
                }
            }else{
                $admin_data['avatar']  = '/assets/img/avatar.png';
                $admin_data['platform_id']  = $row['platform_id'];
                $admin_data['merchant_id']  = $row['id'];
                $admin_id = Admin::insertGetId($admin_data);
                $update['admin_id'] = $admin_id;
                AuthGroupAccess::insert([
                    'uid' => $admin_id,
                    'group_id' => 3,
                ]);
            }
            $this->model->where('id',$row['id'])->update($update);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        }
        return $this->jsonSucc();
    }

}
