<?php

namespace app\admin\controller\merchant;

use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use app\admin\model\Admin;
use fast\Random;
use think\Cache;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Validate;
use app\admin\model\merchant\Merchants;

class Platform extends Backend
{

    protected $model = null;
    protected $platform_id = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\merchant\Platform;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2 || $this_admin_access == 3){
            $this->platform_id = $this->auth->platform_id;
        }else{
            $this->platform_id = $this->request->get('platform_id');
        }
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
        $this->model = new \app\admin\model\merchant\Platform;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2 || $this_admin_access == 3){
            $list = $this->model
                ->where($where)
                ->where('id',$this->platform_id)
                ->order($sort, $order)
                ->paginate($limit);
        }else{
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
        }
        foreach ($list as &$vo){
            $vo['username'] = '';
            $vo['nickname'] = '';
            if(!empty($vo['admin_id'])){
                $admin_info = Admin::where('id',$vo['admin_id'])->find();
                $vo['username'] = $admin_info['username']??'';
                $vo['nickname'] = $admin_info['nickname']??'';
            }
            unset($vo['admin_id']);
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        // 判断账号是否存在
        $this_admin = Admin::where('username', $params['username'])->find();
        if ($this_admin) {
            $this->error('账号已存在');
        }
        if (!Validate::is($params['password'], '\S{6,16}')) {
            $this->error('密码长度必须在6-30位之间，不能包含空格"');
        }
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $add_data = [
                'name' => $params['name'],
                'createtime' => time(),
                'status' => $params['status'],
            ];
            $platform_id = $this->model->insertGetId($add_data);
            $salt = Random::alnum();
            $admin_data = [
                'username' => $params['username'],
                'nickname' => $params['name'],
                'salt' => $salt,
                'password' => $this->auth->getEncryptPassword($params['password'], $salt),
                'avatar' => '/assets/img/avatar.png',
                'platform_id' => $platform_id,
            ];
            $admin_id = Admin::insertGetId($admin_data);
            AuthGroupAccess::insert([
                'uid' => $admin_id,
                'group_id' => 2,
            ]);
            $this->model->where('id',$platform_id)->update(['admin_id'=>$admin_id]);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success();
    }




    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $AuthGroupAccessStatus = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2 || $this_admin_access == 3){
            $AuthGroupAccessStatus = 2;
            $row = $this->model->where('id',$this->auth->platform_id)->find();
        }else{
            $row = $this->model->get($ids);
        }
        $admin_select_info = Admin::where('id', $row['admin_id'])->find();
        if(!$admin_select_info){
            $row['admin_id'] = '';
        }


        if (false === $this->request->isPost()) {
            $row['username'] = '';
            if(!empty($row['admin_id'])){
                $admin_info = Admin::where('id', $row['admin_id'])->field('id,username')->find();
                $row['username'] = $admin_info['username']??'';
            }
            $this->view->assign('row', $row);
            $this->view->assign('AuthGroupAccessStatus', $AuthGroupAccessStatus);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $admin_data = [];
        if (!empty($params['password'])) {
            if (!Validate::is($params['password'], '\S{6,16}')) {
                $this->error('密码长度必须在6-30位之间，不能包含空格"');
            }
            $admin_data['salt'] = Random::alnum();
            $admin_data['password'] = $this->auth->getEncryptPassword($params['password'], $admin_data['salt']);
        }
        Db::startTrans();
        if($AuthGroupAccessStatus == 1){
            // 判断账号是否存在
            if(!empty($row['admin_id'])){
                $this_admin_where['id'] = ['neq', $row['admin_id']];
                $this_admin_where['username'] = ['eq', $params['username']];
                $this_admin = Admin::where($this_admin_where)->find();
                if ($this_admin) {
                    $this->error('账号已存在');
                }
            }else{
                $this_admin_where['username'] = ['eq', $params['username']];
                $this_admin = Admin::where('username',$params['username'])->find();
                if ($this_admin) {
                    $this->error('账号已存在');
                }
            }
            $admin_data['username'] = $params['username'];
        }
        $admin_data['nickname'] = $params['name'];
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $update_data['name'] = $params['name'];
            $update_data['updatetime'] = time();
            if($AuthGroupAccessStatus == 1){
                $update_data['status'] = $params['status'];
                if($params['status'] == 1){
                    $admin_data['status'] = 'normal';
                }else{
                    $admin_data['status'] = 'hidden';
                }
            }

            if(!empty($row['admin_id'])){
                if(!empty($admin_data)){
                    Admin::where('id',$row['admin_id'])->update($admin_data);
                }
            }else{
                $admin_data['avatar']  = '/assets/img/avatar.png';
                $admin_data['platform_id']  = $row['id'];
                $admin_id = Admin::insertGetId($admin_data);
                $update_data['admin_id'] = $admin_id;
                AuthGroupAccess::insert([
                    'uid' => $admin_id,
                    'group_id' => 2,
                ]);
            }
            $this->model->where('id',$row['id'])->update($update_data);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success();
    }


    public function platform_admin()
    {
        $this->model = new Merchants();
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是Selectpage，则转发到Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $field = "id,admin_id,token,secret,name,mark";

        $current_permission = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2){
            $current_permission = 2;
        }else if($this_admin_access == 3){
            $current_permission = 2;
        }
        $list = $this->model
            ->where($where)
            ->where('platform_id', $this->platform_id)
            ->order($sort, $order)
            ->field($field)
            ->paginate($limit);
        foreach ($list as &$vo){
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
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }



    /**
     * 子账号添加
     */
    public function platform_admin_add()
    {
        $this->model = new Merchants();
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }

        // 判断账号是否存在
        $this_admin = Admin::where('username', $params['username'])->find();
        if ($this_admin) {
            $this->error('账号已存在');
        }
        Db::startTrans();
        try {
            $params = $this->request->post('row/a');
            $add_data = [
                'token' => $params['token'],
                'secret' => $params['secret'],
                'name' => $params['name'],
                'rtp' => $params['rtp'],
                'cash_get_url' => $params['cash_get_url'],
                'cash_transferinout_url' => $params['cash_transferinout_url'],
                'mark' => $params['mark'],
                'style_name_switch' => $params['style_name_switch'],
                'style_bb_switch' => $params['style_bb_switch'],
                'style_ec_switch' => $params['style_ec_switch'],
                'style_oj_switch' => $params['style_oj_switch'],
                'type' => 0,
                'status' => $params['status'],
                'created_at' => date('Y-m-d H:i:s'),
                'platform_id' => $this->platform_id,
            ];
            $merchants_id = $this->model->insertGetId($add_data);


            $salt = Random::alnum();
            $admin_data = [
                'username' => $params['username'],
                'nickname' => $params['name'],
                'salt' => $salt,
                'password' => $this->auth->getEncryptPassword($params['password'], $salt),
                'avatar' => '/assets/img/avatar.png',
                'platform_id' => $this->platform_id,
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
            $this->error($e->getMessage());
        }
        $this->success();
    }


    public function platform_admin_edit($ids = null)
    {
        $this->model = new Merchants();
        $AuthGroupAccessStatus = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2 || $this_admin_access == 3){
            $AuthGroupAccessStatus = 2;
            $row = $this->model->where('id',$ids)->where('platform_id',$this->auth->platform_id)->find();
        }else{
            $row = $this->model->where('id',$ids)->find();
        }
        if (!$row) {
            $this->error(__('No Results were found'));
        }



        $admin_select_info = Admin::where('id', $row['admin_id'])->find();
        if(!$admin_select_info){
            $row['admin_id'] = '';
        }


        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $row['username'] = '';
            if(!empty($row['admin_id'])){
                $admin_info = Admin::where('id', $row['admin_id'])->field('id,username')->find();
                $row['username'] = $admin_info['username']??'';
            }
            $this->view->assign('AuthGroupAccessStatus', $AuthGroupAccessStatus);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        //是否采用模型验证
        if ($this->modelValidate) {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
            $row->validateFailException()->validate($validate);
        }


        $admin_data = [];
        if (!empty($params['password'])) {
            if (!Validate::is($params['password'], '\S{6,16}')) {
                $this->error('密码长度必须在6-30位之间，不能包含空格"');
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
                    $this->error('账号已存在');
                }
            }else{
                $this_admin_where['username'] = ['eq', $params['username']];
                $this_admin = Admin::where('username',$params['username'])->find();
                if ($this_admin) {
                    $this->error('账号已存在');
                }
            }
            $update['name'] = $params['name'];
            $update['token'] = $params['token'];
            $update['secret'] = $params['secret'];
            $update['mark'] = $params['mark'];
            $update['status'] = $params['status'];
            $admin_data['username'] = $params['username'];
            $admin_data['nickname'] = $params['name'];
        }

        $update['rtp'] = $params['rtp'];
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
            $this->error($e->getMessage());
        }
        $this->success();
    }
}
