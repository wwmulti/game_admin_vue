<?php

namespace app\adminapi\controller;

use think\Validate;
use think\Hook;
use app\admin\model\AdminLog;
use app\common\utils\JwtAuth;

/**
 * 后台登陆
 * Class Login
 * @package app\adminapi\controller
 */
class Login extends MainController
{  
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
            /* $data = ['id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar];
            $data['token'] = $jwtAuth->getToken($this->auth->id, time()); */
            $tokenInfo = $jwtAuth->getToken($this->auth->id, time());
            $data = [
                'token' => $tokenInfo['token'],
            ];

            return $this->jsonSucc($data, __('Login successful'));

        } else {
            $msg = $this->auth->getError();
            $msg = $msg ? $msg : __('Username or password is incorrect');
            return $this->jsonFail($msg);
        }
    }
}