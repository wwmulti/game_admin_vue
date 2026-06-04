<?php

namespace app\adminapi\controller;

use app\admin\model\Admin;
use app\common\controller\Api;
use fast\Random;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDOStatement;
use think\Controller;
use app\common\utils\JwtAuth;
use app\admin\library\Auth;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Env;
use think\exception\DbException;
use think\Model;
use think\Request;
use think\response\Json;
use think\Validate;
use think\Lang;
use app\common\exception\ApiException;
use app\common\services\SystemRoleServices;

/**
 * 基类 所有控制器继承的类
 * Class MainController
 * @package app\adminapi\controller
 */
class MainController extends Controller
{
	protected $request;

	protected $auth;
	
	/**
	 *
	 * /**
	 * 当前登陆管理员ID
	 * @var
	 */
	protected $adminId;
	/**
	 * 不需要登录的api controller action都配置小写的
	 */
	protected $notLoginApi = ['login/login'];


	/**
	 * 构造方法
	 * @access public
	 * @param App $app 应用对象
	 */
	public function __construct()
	{
		// 控制器初始化
		$this->init();
	}

	/**
	 * 初始化
	 */
	final private function init()
	{
		$this->initialize();
	}


    /**
     * 初始化
     */
    protected function initialize()
    {
        $lang = request()->param('lang');
        if (!in_array($lang, config('allow_lang_list'))) {
            $lang = 'zh-cn';
        }
        Lang::range($lang);

        if(!\think\Request::instance()->isCli()){
            $controller = strtolower(request()->controller());
            $action = strtolower(request()->action());
            $rule = $controller . '/' . $action;
			if (!in_array($rule, $this->notLoginApi)) {
				$authHeader = request()->header('Authorization');
				if (!$authHeader) {
					throw new \think\exception\HttpResponseException(
						json(['msg' => __('Please log in again'), 'code' => 410000])
					);
				}
				// 兼容 "Bearer token" 或 "Bearer\ntoken" 等格式
				$token = trim(str_replace('Bearer', '', $authHeader));
				$jwtAuth = new JwtAuth();
                try {
                    $result = $jwtAuth->parseToken($token);
                    $this->adminId = $result[0];               
                } catch(\Exception $e) {        
                    throw new \think\exception\HttpResponseException(
                        json(['msg' => __('Please log in again'), 'code' => 410000])
                    );
                }

                $roleService = new SystemRoleServices();
                if ($this->adminId && !$roleService->verifiAuth($rule, $this->adminId)) {
                    throw new \think\exception\HttpResponseException(
                        json(['msg' => '没有权限', 'code' => 410001])
                    );
                }		
			}

			$this->request = request();
			$this->auth = new Auth();
		}
	}

	protected function jsonSucc($data = [], $msg = 'success')
	{
		return json(['code' => 200, 'data' => $data, 'msg' => $msg]);
	}

	protected function jsonFail($msg = 'fail', $code = 500)
	{
		return json(['code' => $code, 'msg' => $msg]);
	}

	/**
	 * 获取当前登录管理员的信息
	 *
	 * @return Admin|array|bool|PDOStatement|string|Model|Json
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 * @throws DbException
	 */
	public function getAdmin()
	{
		$adminId = $this->adminId;
		$admin = Admin::alias('a')
			->field('a.*,g.group_id')
			->join('auth_group_access g', 'a.id = g.uid', 'left')
			->where('a.id', $adminId)
			->find();
		if (!$admin) {
			return $this->jsonFail('用户未登录');
		} else {
			return $admin;
		}
	}   

    /**
     * 获取分页配置
     * @param bool $isPage
     * @param bool $isRelieve
     * @return int[]
     */
    public function getPageValue(bool $isPage = true, bool $isRelieve = true)
    {
        $page = 0;
        $limit = 20;
        if ($isPage) {
            $page = request()->param('page'. '/d', 0);
            $limit = request()->param('limit' . '/d', $limit);
        }
        if($page <= 0){
            $page = 1;
        }
        
        return [(int)$page, (int)$limit];
    }    
}
