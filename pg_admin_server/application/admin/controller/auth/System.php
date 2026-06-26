<?php

namespace app\admin\controller\auth;

use app\common\services\SystemMenuServices;
use app\common\services\SystemRoleServices;
use think\Db;
use fast\Tree;
use app\admin\model\AuthRule;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\Admin;
use app\admin\model\AdminLog;
use think\Validate;
use fast\Random;
use think\Loader;
use PHPGangsta_GoogleAuthenticator;
use think\Config;
use app\common\library\Upload;
use app\common\controller\Backend;

class System extends Backend
{
    /**
     * 获取首页菜单
     */
    public function menus()
    {
        $services = new SystemMenuServices();
        $menuTree = $services->getMenusTreeByCache($this->adminId, $this->auth);
        // $authRuleIds = $this->auth->getRuleIds($this->adminId);
        // return $this->jsonSucc(['menuTree' => $menuTree, 'authRuleIds' => $authRuleIds]);
        return $this->jsonSucc($menuTree);
    }

    public function allAuthMenus()
    {
        $services = new SystemMenuServices();
        $menuTree = $services->getAllMenusTreeByCache($this->adminId, $this->auth);
        // $authRuleIds = $this->auth->getRuleIds($this->adminId);
        // return $this->jsonSucc(['menuTree' => $menuTree, 'authRuleIds' => $authRuleIds]);
        return $this->jsonSucc($menuTree);
    }

    /**
     * 获取菜单规则
     */
    public function auths()
    {
        $services = new SystemMenuServices();
        $result = $services->getMenusList($this->adminId);
        return $this->jsonSucc($result);
    }

    /**
     * 菜单编辑
     */
    public function authedit()
    {
        $id = request()->post('id', 0, 'intval');
        $row = model('app\common\model\AuthRule')->get(['id' => $id]);

        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }

        $ismenu = request()->post('tag', 0, 'intval');
        $pid = request()->post('pid', 0, 'intval');
        $status = request()->post('status', 1, 'intval');

        if (!$ismenu && !$pid) {
            return $this->jsonFail(__('The non-menu rule must have parent'));
        }

        if ($pid == $row['id']) {
            return $this->jsonFail(__('Can not change the parent to self'));
        }

        if ($pid != $row['pid']) {
            $childrenIds = Tree::instance()->init(collection(AuthRule::select())->toArray())->getChildrenIds($row['id']);
            if (in_array($pid, $childrenIds)) {
                return $this->jsonFail(__('Can not change the parent to child'));
            }
        }
        $params = [
            'pid' => $pid,
            'title' => request()->post('title', '', 'strip_tags'),
            'name' => request()->post('uri', '', 'strip_tags'),
            'icon' => request()->post('icon', '', 'strip_tags'),
            'weigh' => request()->post('sort', 0, 'intval'),
            'ismenu' => $ismenu,
            'status' => $status ? 'normal' : 'disabled',
            'component' => request()->post('component', '', 'strip_tags'),
            'route_name' => request()->post('name', '', 'strip_tags'),
            'path' => request()->post('path', '', 'strip_tags'),
        ];

        if ($ismenu) {
            $check = model('app\common\model\AuthRule')->get(['route_name' => $params['route_name']]);
            if ($check && $check['id'] != $row['id']) {
                return $this->jsonFail(__('The route name already exists'));
            }
        } else {
            $params['path'] = '';
            $params['route_name'] = '';
            $params['component'] = '';
        }

        //这里需要针对name做唯一验证
        $ruleValidate = \think\Loader::validate('AuthRule', 'validate', false, 'admin');
        $ruleValidate->rule([
            'name' => 'require|unique:AuthRuleApi,name,' . $row->id,
            // 'route_name' => 'require|unique:AuthRuleApi,route_name,' . $row->id,
        ]);
        $result = $row->validate()->save($params);
        if ($result === false) {
            return $this->jsonFail($row->getError());
        }

        $this->auth->clearMenuCache();
        return $this->jsonSucc();
    }

    /**
     * 创建菜单
     */
    public function authadd()
    {
        $ismenu = request()->post('tag', 0, 'intval');
        $pid = request()->post('pid', 0, 'intval');
        $status = request()->post('status', 1, 'intval');
        if (!$ismenu && !$pid) {
            return $this->jsonFail(__('The non-menu rule must have parent'));
        }

        $params = [
            'pid' => $pid,
            'title' => request()->post('title', '', 'strip_tags'),
            'name' => request()->post('uri', '', 'strip_tags'),
            'icon' => $ismenu ? request()->post('icon', '', 'strip_tags') : '',
            'weigh' => request()->post('sort', 0, 'intval'),
            'ismenu' => $ismenu,
            'status' => $status ? 'normal' : 'disabled',
            'component' => request()->post('component', '', 'strip_tags'),
            'route_name' => request()->post('name', '', 'strip_tags'),
            'path' => request()->post('path', '', 'strip_tags'),
        ];

        $validate = new \app\common\validate\AuthRule();
        if (!$validate->scene('add')->check($params)) {
            return $this->jsonFail($validate->getError());
        }

        $model = model('app\common\model\AuthRule');
        $check = $model->where('name', '=', $params['name'])->find();
        if ($check) {
            return $this->jsonFail(__('The name already exists'));
        }

        if ($params['ismenu'] == 1) {
            $check1 = $model->where('route_name', '=', $params['route_name'])->find();
            if ($check1) {
                return $this->jsonFail(__('The route_name already exists'));
            }
        } else {
            $params['path'] = '';
            $params['route_name'] = '';
            $params['component'] = '';
        }

        
        $result = $model->create($params);
        if ($result === false) {
            return $this->jsonFail($model->getError());
        }

        $this->auth->clearMenuCache();
        return $this->jsonSucc();       
    }

    /**
     * 删除菜单
     */
    public function authdel()
    {
        $id = request()->post('id', 0, 'intval');
        $model = model('app\common\model\AuthRule');

        // 检查是否有子节点
        $child = $model->where('pid', '=', $id)->find();
        if ($child) {
            return $this->jsonFail(__('Please delete child nodes first'));
        }

        $count = $model->where('id', '=', $id)->delete();

        if ($count) {
            $this->auth->clearMenuCache();
        }
        return $this->jsonSucc(); 
    }

    public function authstatus()
    {
        $id = request()->post('id', 0, 'intval');
        $status = request()->post('status', 1, 'intval');
        $model = model('app\common\model\AuthRule');

        $row = $model->get(['id' => $id]);
        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }

        $count = $model->where('id', '=', $id)->update(['status' => $status ? 'normal' : 'disabled']);

        if ($count) {
            $this->auth->clearMenuCache();
        }
        return $this->jsonSucc(); 
    }

    /**
     * 角色列表
     */
    public function roles() 
    {  
        [$page, $limit] = $this->getPageValue();

        $childrenGroupIds = $this->auth->getChildrenGroupIds(true, $this->adminId);
        $condition = [
            'id' => ['in', $childrenGroupIds]
        ];


        $db = new AuthGroup;
        $result = [
            'total' => $db->where($condition)->count(),
            'list' => $db->where($condition)->field('*')->page($page)->limit($limit)->order('id asc')->select()
        ];
        $result['list'] = array2level($result['list']);
        return $this->jsonSucc($result);
    }

    /**
     * 角色编辑
     */
    public function roleedit()
    {
        $id = request()->post('id', 0, 'intval');
        $status = request()->post('status', 'normal', 'addslashes');
        $roleName = request()->post('role_name', '', 'trim');
        $pid = request()->post('pid', 0, 'intval');


        $childrenGroupIds = $this->auth->getChildrenGroupIds(false, $this->adminId);

        if (!in_array($id, $childrenGroupIds)) {
            return $this->jsonFail(__('You have no permission'));
        }

        $model = new AuthGroup;
        $row = $model->get(['id' => $id]);
        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }

        if (!$this->auth->isSuperAdmin($this->adminId)) {      
            //父节点不能是非权限内节点
            if (!in_array($pid, $childrenGroupIds)) {
                return $this->jsonFail(__('The parent group exceeds permission limit'));
            }

            $parentmodel = $model->get($pid);
            if (!$parentmodel) {
                return $this->jsonFail(__('The parent group can not found'));
            }
        }

        // 父节点不能是自身或其子节点
        $groupList = collection($model->select())->toArray();
        Tree::instance()->init($groupList);
        if (in_array($pid, Tree::instance()->getChildrenIds($row->id, true))) {
            return $this->jsonFail(__('The parent group can not be its own child or itself'));
        }
        
        $params = [
            'status' => $status,
            'pid' => $pid,
            'name' => $roleName,
        ];
        $row->save($params);       
        return $this->jsonSucc();
    }

    /**
     * 添加角色
     */
    public function roleadd()
    {
        $pid = request()->post('pid', 0, 'intval');
        $roleName = request()->post('role_name', '', 'trim');
        
        if (empty($roleName)) {
            return $this->jsonFail(__('Please input role name'));
        }

        $childrenGroupIds = $this->auth->getChildrenGroupIds(false, $this->adminId);
    
        $model = new AuthGroup;
        if (!$this->auth->isSuperAdmin($this->adminId)) {      
            //父节点不能是非权限内节点
            if (!in_array($pid, $childrenGroupIds)) {
                return $this->jsonFail(__('The parent group exceeds permission limit'));
            }            
        }
        $params = [
            'pid' => $pid,
            'name' => $roleName,
            'status' => 'normal',
            'rules' => ''
        ];
        $model->create($params);
        return $this->jsonSucc();
    }

    /**
     * 删除角色
     */
    public function roledel()
    {
        $id = request()->post('id', 0, 'intval');

        $childrenGroupIds = $this->auth->getChildrenGroupIds(false, $this->adminId);
        if (!in_array($id, $childrenGroupIds)) {
            return $this->jsonFail(__('You have no permission'));
        }

        $grouplist = $this->auth->getGroups($this->adminId);
        $groupIds = array_map(function ($group) {
            return $group['id'];
        }, $grouplist);
        // 移除掉当前管理员所在组别
        $ids = array_diff([$id], $groupIds);

        $model = new AuthGroup;
        $grouplist = $model->where('id', 'in', $ids)->select();

        $groupaccessmodel = new AuthGroupAccess;
        foreach ($grouplist as $k => $v) {
            // 当前组别下有管理员
            $groupone = $groupaccessmodel->get(['group_id' => $v['id']]);
            if ($groupone) {
                $ids = array_diff($ids, [$v['id']]);
                continue;
            }
            // 当前组别下有子组别
            $groupone = $model->get(['pid' => $v['id']]);
            if ($groupone) {
                $ids = array_diff($ids, [$v['id']]);
                continue;
            }
        }

        if (!$ids) {
            return $this->jsonFail(__('You can not delete group that contain child group and administrators'));
        }
        $model->where('id', $id)->delete();
        return $this->jsonSucc();
    }

    /**
     * 角色授权列表
     */
    public function rolerules()
    {
        $id = request()->get('id', 0, 'intval');
        $services = new SystemRoleServices();
        return $this->jsonSucc($services->getRoleRules($id));
    }

    /**
     * 角色权限修改
     */
    public function rolerulesedit()
    {
        $id = request()->post('id', 0, 'intval');
        $ruleIds = request()->post('auth_rule_ids', '', 'trim');

        $services = new SystemRoleServices();
        list($status, $msg) = $services->editRoleRule($id, explode(',', $ruleIds));
        $this->auth->clearMenuCache();

        return $status ? $this->jsonSucc() : $this->jsonFail($msg);
    }

    /**
     * 获取管理员日志
     */
    public function adminlog()
    {
        $adminId = request()->get('adminId', 0, 'intval');
        $title = request()->get('title', '', 'addslashes');
        $username = request()->get('username', '', 'addslashes');
        $url = request()->get('url', '', 'addslashes');
        $ip = request()->get('ip', '', 'addslashes');
        list($startDate, $endDate) = request()->get('createTime/a');

        $isSuperAdmin = $this->auth->isSuperAdmin($this->adminId);   
        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);

        [$page, $limit] = $this->getPageValue(); 

        $condition = [];
        if (!empty($startDate) && !empty($endDate)) {
            $condition = [
                'createtime' => [['>=', strtotime($startDate)], ['<', strtotime('+1days', strtotime($endDate))]]
            ];
        }        
        if (!empty($adminId)) {
            $condition['admin_id'] = $adminId;
        }
        if (!empty($title)) {
            $condition['title'] = ['like', '%'. $title .'%'];
        }
        if (!empty($username)) {
            $condition['username'] = ['like', '%' . $username . '%'];
        }
        if (!empty($url)) {
            $condition['url'] = $url;
        }
        if (!empty($ip)) {
            $condition['ip'] = $ip;
        }       


        $list = AdminLog::where($condition)
                ->where(function ($query) use ($isSuperAdmin, $childrenAdminIds) {
                    if (!$isSuperAdmin) {
                        $query->where('admin_id', 'in', $childrenAdminIds);
                    }
                })
              ->field('*,createtime as recordTime')
              ->page($page)->limit($limit)->order('id desc')->select();

        $result = [
            'total' => AdminLog::where($condition)->count(),
            'list' => $list
        ];      
        return $this->jsonSucc($result);
    }

    /**
     * 删除管理员日志
     */
    public function delAdminLog()
    {
        $idStr = request()->post('ids');
        $ids = explode(',', $idStr);
        $ids = array_map('intval', $ids);

        $isSuperAdmin = $this->auth->isSuperAdmin($this->adminId);
        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);

        $model = new AdminLog;
        $adminList = $model->where('id', 'in', $ids)
            ->where(function ($query) use ($isSuperAdmin, $childrenAdminIds) {
            if (!$isSuperAdmin) {
                $query->where('admin_id', 'in', $childrenAdminIds);
            }
        })->select();

        if ($adminList) {
            $deleteIds = [];
            foreach ($adminList as $k => $v) {
                $deleteIds[] = $v->id;
            }
            if ($deleteIds) {
                $model->destroy($deleteIds);
            }
        }

        return $this->jsonSucc();
    }

    /**
     * 管理员列表
     */
    public function adminList()
    {
        [$page, $limit] = $this->getPageValue();       

        $id = request()->get('id', 0 , 'intval');
        $username = request()->get('username', '', 'addslashes');
        $nickname = request()->get('nickname', '', 'addslashes');
        $status = request()->get('status', '', 'addslashes');
        list($startDate, $endDate) = request()->get('createTime/a');

        $condition = [];
        if (!empty($id)) {
            $condition['a.id'] = $id;
        }
        if (!empty($username)) {
            $condition['username'] = ['like', '%'. $username .'%'];
        }
        if (!empty($nickname)) {
            $condition['nickname'] = ['like', '%'. $nickname .'%'];
        }
        if (!empty($status)) {
            $condition['a.status'] = $status;
        }
        if (!empty($startDate) && !empty($endDate)) {
            $condition['a.createtime'] = [['>=', strtotime($startDate)], ['<', strtotime('+1days', strtotime($endDate))]];
        }

        $model = new Admin;

        $list = $model->alias('a')->join('auth_group_access_api b', 'a.id=b.uid', 'left')
            ->join('auth_group_api c', 'b.group_id=c.id', 'left')
            //         ->field(' a.id,  a.username,  a.nickname, a.loginfailure,   a.loginip, a.status,  c.name AS groupName, b.group_id, IF(a.logintime IS NOT NULL, 
            //    DATE_FORMAT(FROM_UNIXTIME(logintime), "%Y-%m-%d %H:%i:%s"), 
            //    NULL) AS loginTimeFormatted')
            ->field(' a.id,  a.username,  a.nickname, a.loginfailure,   a.loginip, a.status, a.google_switch, a.google_verify, c.name AS groupName, b.group_id, a.logintime AS loginTimeFormatted')
            ->where($condition)->order('a.id desc')->page($page)->limit($limit)->select();

        
        $result = [
            'total' => $model->alias('a')->where($condition)->count(),
            'list' => $list
        ];      
        return $this->jsonSucc($result);
    }

    /**
     * 管理员修改
     */
    public function adminEdit()
    {
        $id = request()->post('id', 0, 'intval');
        $username = request()->post('username', '', 'addslashes');
        $nickname = request()->post('nickname', '', 'addslashes');
        $password = request()->post('password', '', 'addslashes');
        $loginfailure = request()->post('loginfailure', 0, 'intval');
        $groupId = request()->post('groupId', 0, 'intval');
        $status = request()->post('status', 'normal', 'addslashes');


        $model = new Admin;

        $row = $model->get(['id' => $id]);        
        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }

        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);
        if (!in_array($row->id, $childrenAdminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }

        if (!empty($password) && !Validate::is($password, '\S{6,30}')) {
            return $this->jsonFail(__('Please input correct password'));
        }

        // 过滤不允许的组别,避免越权
        $childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin($this->adminId), $this->adminId);

        $group = array_intersect($childrenGroupIds, [$groupId]);
        if (!$group) {
            return $this->jsonFail(__('The parent group exceeds permission limit'));
        }

        Db::startTrans();
        try {
            $params = [
                'username' => $username,
                'nickname' => $nickname,
                'status' => $status,
                'loginfailure' => $loginfailure
            ];

            if (!empty($password)) {              
                $params['salt'] = Random::alnum();
                $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
            }

            $adminValidate = \think\Loader::validate('Admin', 'validate', false, 'admin');
            $adminValidate->rule([
                'username' => 'require|regex:\w{3,30}|unique:admin,username,' . $row->id,
                'password' => 'regex:\S{32}',
            ]);
            $result = $row->validate('Admin.edit')->save($params);
            if ($result === false) {
                return $this->jsonFail($row->getError());
            }

            // 先移除所有权限
            (new AuthGroupAccess)->where('uid', $row->id)->delete();           

            $dataset = [];
            foreach ($group as $value) {
                $dataset[] = ['uid' => $row->id, 'group_id' => $value];
            }
            (new AuthGroupAccess)->saveAll($dataset);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        }
       
        return $this->jsonSucc();
    }

    /**
     * 管理员删除
     */
    public function adminDel()
    {
        $idStr = request()->post('ids');
        $ids = explode(',', $idStr);
        $ids = array_map('intval', $ids);
    
        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);
        $ids = array_intersect($childrenAdminIds, $ids);

        $childrenGroupIds = $this->auth->getChildrenGroupIds(true, $this->adminId);
        $model = new Admin;
        $adminList = $model->where('id', 'in', $ids)->where('id', 'in', function ($query) use ($childrenGroupIds) {
            $query->name('auth_group_access_api')->where('group_id', 'in', $childrenGroupIds)->field('uid');
        })->select();
        if ($adminList) {
            $deleteIds = [];
            foreach ($adminList as $k => $v) {
                $deleteIds[] = $v->id;
            }
            $deleteIds = array_values(array_diff($deleteIds, [$this->adminId]));
            if ($deleteIds) {
                Db::startTrans();
                try {
                    $model->destroy($deleteIds);
                    (new AuthGroupAccess)->where('uid', 'in', $deleteIds)
                        ->where('group_id', 'in', $childrenGroupIds)
                        ->delete();
                    Db::commit();
                    return $this->jsonSucc();
                } catch (\Exception $e) {
                    Db::rollback();
                    return $this->jsonFail($e->getMessage());
                }
            }
            return $this->jsonFail(__('No rows were deleted'));
        }
    }

    /**
     * 管理员添加
     */
    public function adminAdd()
    {
        $username = request()->post('username', '', 'addslashes');
        $nickname = request()->post('nickname', '', 'addslashes');
        $password = request()->post('password', '', 'addslashes');
        $groupId = request()->post('groupId', 0, 'intval');
        $status = request()->post('status', 'normal', 'addslashes');

        if (!empty($password) && !Validate::is($password, '\S{6,30}')) {
            return $this->jsonFail(__('Please input correct password'));
        }

        $params = [
            'username' => $username,
            'nickname' => $nickname,
            'salt' => Random::alnum(),
            'avatar' => '/assets/img/avatar.png', //设置新管理员默认头像。
            'status' => $status
        ];
        $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);

        Db::startTrans();
        $model = new Admin;

        $childrenGroupIds = $this->auth->getChildrenGroupIds(true, $this->adminId);
        //过滤不允许的组别,避免越权
        $group = array_intersect($childrenGroupIds, [$groupId]);
        if (!$group) {
            return $this->jsonFail(__('The parent group exceeds permission limit'));
        }

        try {
            $result = $model->validate('app\admin\validate\Admin.add')->save($params);
            if ($result === false) {
                return $this->jsonFail($model->getError());
            }

            $dataset = [];
            foreach ($group as $value) {
                $dataset[] = ['uid' => $model->id, 'group_id' => $value];
            }
            (new AuthGroupAccess)->saveAll($dataset);
            Db::commit();
            return $this->jsonSucc();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        } 
    }

    public function adminAttrEdit()
    {
        $id = request()->post('id', 0, 'intval');
        $field = request()->post('field', '', 'trim');
        $value = request()->post('value', '');

        if (!$id || !$field) {
            return $this->jsonFail(__('Parameter error'));
        }

        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);
        if (!in_array($id, $childrenAdminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }

        $allowedFields = ['status', 'google_switch'];
        if (!in_array($field, $allowedFields)) {
            return $this->jsonFail(__('Field not allowed'));
        }

        $model = new Admin;
        $model->where(['id' => $id])->update([$field => $value]);
        return $this->jsonSucc();
    }

    /**
     * 解绑谷歌
     */
    public function unbinding()
    {
        $id = request()->post('id', 0, 'intval');
        if (!$id) {
            return $this->jsonFail(__('Parameter error'));
        }

        $childrenAdminIds = $this->auth->getChildrenAdminIds(true, $this->adminId);
        if (!in_array($id, $childrenAdminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }

        $model = new Admin;
        $row = $model->where('id', $id)->find();
        if ($row && $row['google_verify']) {
            $model->where('id', $id)->update([
                'google_verify' => '',
                'google_switch' => 0,
            ]);
            return $this->jsonSucc(__('解绑成功'));
        }
        return $this->jsonFail(__('操作失败，请联系管理员'));
    }

    /**
     * 用户个人信息
     */
    public function profile()
    {
        $model = new Admin;
        $field = 'username,nickname,avatar,google_verify,google_switch';
        $info = $model->where(['id' => $this->adminId])->field($field)->find();

        $qrCodeUrl = '';
        if (!empty($info['google_verify'])) {            
           // 生成谷歌验证秘钥，保存
            Loader::import('PHPGangsta.GoogleAuthenticator', EXTEND_PATH);
            $ga = new \PHPGangsta_GoogleAuthenticator();
            $qrCodeUrl = 'otpauth://totp/'.'gameoc_' . $info['username'].'?secret='.$info['google_verify'];
        }
        $info['qrCodeUrl'] = $qrCodeUrl;
        $info['google_switch'] = intval($info['google_switch']);
        return $this->jsonSucc($info);
    }

    /**
     * 更新个人信息
     */
    public function updateProfile()
    {
        $nickname = request()->post('nickname', '', 'addslashes');
        $password = request()->post('password', '', 'addslashes');        
        $googleSwitch = request()->post('google_switch', 0, 'intval');
        $avatar = request()->post('img', '', 'addslashes');

        $model = new Admin;

        $params = [
            'google_switch' => $googleSwitch,
            'nickname' => $nickname,
            'avatar' => $avatar
        ];

        if (!empty($password)) {
            if (!Validate::is($password, "/^[\S]{6,30}$/")) {
                return $this->jsonFail(__("Please input correct password"));
            }

            $params['salt'] = Random::alnum();
            $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
        }

        if ($googleSwitch) {
            // 生成谷歌验证秘钥，保存
            Loader::import('PHPGangsta.GoogleAuthenticator', EXTEND_PATH);
            $ga = new PHPGangsta_GoogleAuthenticator();
            $secret = $ga->createSecret();
            $params['google_verify'] = $secret;
        }
        $model->where(['id' => $this->adminId])->update($params);

        return $this->jsonSucc();      
    }

    /**
     * 文件上传
     */
    public function uploadimg()
    {
        $file = $this->request->file('img');
        if(empty($file)){
            return $this->jsonFail(__('Please upload image'));
        }

        try {
            $upload = new Upload($file);
            $attachment = $upload->upload();
        } catch (\Exception $e) {
            return $this->jsonFail($e->getMessage());
        }

        return $this->jsonSucc(['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)], __('Uploaded successful'));
    }
}