<?php

namespace app\common\services;

use think\Cache;
use app\admin\library\Auth;

class SystemMenuServices
{
     /**
     * 从缓存中获取后台菜单树
     */
    public function getMenusTreeByCache($adminId, $auth) {
        /* $cacheKey = 'admin_menus_tree:' . $adminId;
        $redis = Cache::store('redis');
        $authMenus = [];
        if($redis->has($cacheKey)){
            $cacheJson = $redis->get($cacheKey);
            if (!empty($cacheJson)) {
               $authMenus = json_decode($cacheJson, true);
            }
        } */
        if (empty($authMenus)) {
            $authMenus = $this->getMenusTreee($adminId);
            $this->cacheMenusTree($authMenus, $adminId);
        }

        $authMenus = $this->filterMenus($authMenus, $auth, $adminId);

        return $authMenus;
    }

    public function getAllMenusTreeByCache($adminId, $auth) {
        /* $cacheKey = 'admin_all_menus_tree:' . $adminId;
        $redis = Cache::store('redis');
        $authMenus = [];
        if($redis->has($cacheKey)){
            $cacheJson = $redis->get($cacheKey);
            if (!empty($cacheJson)) {
               $authMenus = json_decode($cacheJson, true);
            }
        } */
        if (empty($authMenus)) {
            $authMenus = $this->getMenusTreee($adminId, true);
            $this->cacheAllMenusTree($authMenus, $adminId);
        }


        $authMenus = $this->filterMenus($authMenus, $auth, $adminId);

        return $authMenus;
    }

    /**
     * 后台菜单树
     */
    public function getMenusTreee($adminId, $isAll = false)
    {
        $authMenus = (new Auth())->getMenus(true, $adminId, $isAll);

        $authMenus = !empty($authMenus) ? tidyMenuTier($authMenus) : [];

        $menu_arr = array_column($authMenus, 'weigh');

        array_multisort($menu_arr, SORT_DESC, $authMenus);

        return $authMenus;
    }

    /**
     * 缓存菜单树
     * @param array $authMenus
     */
    public function cacheMenusTree($authMenus=[], $adminId) {
        $cacheKey = 'admin_menus_tree:' . $adminId;
        $redis = Cache::store('redis');

        if (empty($authMenus)) {
            $authMenus = $this->getMenusTreee($adminId);
        }
        $redis->set($cacheKey, json_encode($authMenus));
    }

    /**
     * 缓存所有菜单树
     * @param array $authMenus
     */
    public function cacheAllMenusTree($authMenus=[], $adminId) {
        $cacheKey = 'admin_all_menus_tree:' . $adminId;
        $redis = Cache::store('redis');

        if (empty($authMenus)) {
            $authMenus = $this->getMenusTreee($adminId, true);
        }
        $redis->set($cacheKey, json_encode($authMenus));
    }

     /**
     * 递归过滤没有权限的菜单
     */
    private function filterMenus($menus, $auth, $adminId)
    {
        $filteredMenus = [];
        foreach ($menus as $menu) {
            // if ($adminId == 1 || $auth->check($menu['name'], $adminId)) {
            if ($adminId == 1 || $auth->check($menu['uri'], $adminId, 'or', 'menu', true)) { // name被替换了，用uri代替
                if (isset($menu['children']) && !empty($menu['children'])) {
                    $menu['children'] = $this->filterMenus($menu['children'], $auth, $adminId);
                }
                $filteredMenus[] = $menu;
            }
        }
        
        return $filteredMenus;
    }

     /**
     * 菜单列表
     */
    public function getMenusList($adminId)
    {
        $menuData = (new Auth())->getMenus(true, $adminId, true);
        $menuOptions = array2level($menuData);
        $menuList = !empty($menuData) ? tidyMenuTier($menuData) : [];
        $menuSort = array_column($menuList, 'weigh');
        array_multisort($menuSort, SORT_DESC, $menuList);

        return ['menuList' => $menuList, 'menuOptions' => $menuOptions];
    }
}