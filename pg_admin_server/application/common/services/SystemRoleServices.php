<?php

namespace app\common\services;

use think\Db;
use think\Cache;
use app\admin\library\Auth;
use app\admin\model\AuthGroup;
use app\common\model\AuthRule;
use fast\Tree;

class SystemRoleServices
{
    /**
     * 获取角色的授权信息
     *
     * @param integer $id 角色ID
     */
    public function getRoleRules($id)
    {
        $db = new AuthGroup;      
        
        // 取当前角色信息
        $authGrouData = $db->where('id', $id)->find();

        if(empty($authGrouData)){
            return [];
        }

        $authRules = !empty($authGrouData['rules']) ? explode(',', $authGrouData['rules']) : [];

        $condition = ['status' => 'normal'];
        if (!in_array($authGrouData['pid'], [0,1])) {
            //顶级，获取全部节点       
            $condition = [
                'id' => $authGrouData['pid']
            ];
            $parentRules = $db->where($condition)->value('rules');

            $authRuleIds = [];
            if (!empty($parentRules) && $parentRules != '*') {
                $authRuleIds = explode(',', $parentRules);
                $condition['id'] = ['in', $authRuleIds]; 
            }                   
        } 
       
        $authRuleList = (new AuthRule)->where($condition)->field('*,title as label')->select();
        $authRuleList = !empty($authRuleList) ? tidyMenuTier($authRuleList) : [];
        $menuArr = array_column($authRuleList, 'weigh');
        array_multisort($menuArr, SORT_DESC, $authRuleList);
        
        if ($authGrouData['pid'] == 0) {
            $authIds = [];
            $list = (new AuthRule)->where('status', 'normal')->select();
            foreach ($list as  $value) {
                $authIds[] = $value['id'];
            }
        } else {
            $authIds = $authRules;
        }
       

        return [
            'list' => $authRuleList,
            'auth_ids' => $authIds
        ];
    }

    /**
     * 修改角色权限
     */
    public function editRoleRule($id, $ruleIds)
    {
        $row = AuthGroup::where(['id' => $id])->find();
        if ($row['pid'] == 0) {
            //顶级拥有全部权限
            return [false, __('Super administrators do not need permission settings')];
        }
        $parentRules = AuthGroup::where(['id' => $row['pid']])->value('rules');
        $rules = array_intersect(explode(',', $parentRules), $ruleIds);

        $tree = new Tree();
        $tree->init(AuthGroup::all());
        $childIds = $tree->getChildrenIds($id);

        Db::startTrans();
        try {
            $row->save(['rules' => implode(',', $ruleIds)]);

            $childrenAuthGroups = AuthGroup::all(['id' => ['in', implode(',', $childIds)]]);
            $childParams = [];
            foreach ($childrenAuthGroups as $key => $childrenAuthGroup) {
                $childParams[$key]['id'] = $childrenAuthGroup->id;
                $childParams[$key]['rules'] = implode(',', array_intersect(explode(',', $childrenAuthGroup->rules), $rules));
            }

            (new AuthGroup)->saveAll($childParams);
            Db::commit();
            return [true, 'success'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * 权限验证
     */
    public function verifiAuth($rule, $adminId)
    {
        $auth = new Auth();
        if (in_array($rule, ['system/menus', 'auth/system/menus'])) return true;
        if ($auth->isSuperAdmin($adminId)) return true;
        return $auth->check($rule, $adminId, 'or', 'url', true);
    }
}