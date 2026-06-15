<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Cache;
use think\Config;
use think\Env;
use think\Hook;
use think\Loader;
use think\Session;
use think\Validate;
use app\common\utils\JwtAuth;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout','time_check'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }


    /**
     * 定时10s验证后台显示巴西时间
     */
    public function time_check()
    {
        return json(['time'=>date('Y-m-d H:i:s')]);
    }

    /**
     * 后台首页
     */
    public function index()
    {
        $cookieArr = ['adminskin' => "/^skin\-([a-z\-]+)\$/i", 'multiplenav' => "/^(0|1)\$/", 'multipletab' => "/^(0|1)\$/", 'show_submenu' => "/^(0|1)\$/"];
        foreach ($cookieArr as $key => $regex) {
            $cookieValue = $this->request->cookie($key);
            if (!is_null($cookieValue) && preg_match($regex, $cookieValue)) {
                config('fastadmin.' . $key, $cookieValue);
            }
        }
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
//            'auth/rule' => __('Menu'),
        ], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        $this->assignconfig('cookie', ['prefix' => config('cookie.prefix')]);
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', $referermenu);
        $this->view->assign('title', __('Home'));
        $server_time_name = Env::get('app.server_time_name', '');
        $this->view->assign('server_time_name', $server_time_name);
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    /* public function login()
    {
        $url = $this->request->get('url', '', 'url_clean');
        $url = $url ?: 'index/index';
        if ($this->auth->isLogin()) {
            $this->redirect($url);
//            $this->success(__("You've logged in, do not login again"), $url);
        }
        //保持会话有效时长，单位:小时
        $keeyloginhours = 24;
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password', '', null);
            $keeplogin = $this->request->post('keeplogin');
            $googlecaptcha = $this->request->post('googlecaptcha');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
            ];
            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $googlecaptcha,$keeplogin ? $keeyloginhours * 3600 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            Session::delete("referer");
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = $background ? (stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background) : '';
        $this->view->assign('keeyloginhours', $keeyloginhours);
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    } */

    /**
     * 登陆
     * @return mixed
     */
    public function login()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password', '', null);
        $keeplogin = $this->request->post('keeplogin');
        $googlecaptcha = $this->request->post('googlecaptcha');

        $rule = [
            'username'  => 'require|length:3,30',
            'password'  => 'require|length:3,30',
        ];
        $data = [
            'username'  => $username,
            'password'  => $password,
        ];
        $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);

        $result = $validate->check($data);
        if (!$result) {
            return $this->jsonFail($validate->getError());
        }
        AdminLog::setTitle(__('Login'));

        $result = $this->auth->login($username, $password, $googlecaptcha);
        if ($result === true) {
            Hook::listen("admin_login_after", $this->request);

            $jwtAuth = new JwtAuth();
            $data = ['id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar];
            // $data['token'] = $jwtAuth->getToken($this->auth->id, time());
            $tokenInfo = $jwtAuth->getToken($this->auth->id, time());
            $data['token'] = $tokenInfo['token'];

            return $this->jsonSucc($data, __('Login successful'));
        } else {
            $msg = $this->auth->getError();
            $msg = $msg ? $msg : __('Username or password is incorrect');
            return $this->jsonFail($msg);
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if ($this->request->isPost()) {
            $this->auth->logout();
            Hook::listen("admin_logout_after", $this->request);
            $this->redirect('index/login');
        }
        $html = "<form id='logout_submit' name='logout_submit' action='' method='post'>" . token() . "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['logout_submit'].submit();</script>";
        return $html;
    }



    /**
     * 谷歌验证
     *
     * @return void
     * @throws \think\exception\DbException
     */
    public function verify()
    {
        $username = input('username') ?: '';
        if ($username === 'system' || config('app_env') !== 'pro') {
            $this->success('success', null, ['is_show' => false]);
        }
        $password = input('password') ?: '';
        $rule = [
            'username' => 'require|length:2,12',
            'password' => 'require|length:6,16',
        ];
        $data = [
            'username' => $username,
            'password' => $password,
        ];
        $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password')]);
        $result = $validate->check($data);
        if (!$result) {
            $this->error($validate->getError());
        }

        $admin = Admin::get(['username' => $username]);
        if (!$admin) {
            $this->error('账号密码有误，请重新输入');
        }
        if ($admin['status'] == 'hidden') {
            $this->error('您的账号已被禁用，请联系管理员处理');
        }
        if ($admin->password != $this->auth->getEncryptPassword($password, $admin->salt)) {
            $this->error('账号密码有误，请重新输入');
        }

        $isShow = false;
        $secret = $admin['google_verify'];
        $qrCodeUrl = '';
        if (!$secret) {
            // 生成谷歌验证秘钥，保存
            Loader::import('PHPGangsta.GoogleAuthenticator', EXTEND_PATH);
            $ga = new \PHPGangsta_GoogleAuthenticator();
            $secret = $ga->createSecret();
            $qrCodeUrl = $ga->getQRCodeGoogleUrl('gameoc_' . $username, $secret, 'googleVerify', ['width' => 100, 'height' => 100]);
            Cache::store('redis')->handler()->set('google_verify:' . $username, $secret);
            $isShow = true;
        }

        if ($isShow) {
            $this->success('success', null, ['url' => $qrCodeUrl, 'secret' => $secret, 'is_show' => $isShow]);
        } else {
            $this->result([], 401, '已绑定谷歌验证码，请输入验证码');
        }
    }
}
