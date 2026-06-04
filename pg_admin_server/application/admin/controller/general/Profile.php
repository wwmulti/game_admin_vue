<?php

namespace app\admin\controller\general;

use app\admin\model\Admin;
use app\common\controller\Backend;
use fast\Random;
use think\Loader;
use think\Session;
use think\Validate;
use PHPGangsta_GoogleAuthenticator;

/**
 * 个人配置
 *
 * @icon fa fa-user
 */
class Profile extends Backend
{

    protected $searchFields = 'id,title';

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $this->model = model('AdminLog');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->where('admin_id', $this->auth->id)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        // 生成谷歌验证秘钥，保存
        Loader::import('PHPGangsta.GoogleAuthenticator', EXTEND_PATH);
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $this->auth->google_verify;
//        $qrCodeUrl = $ga->getQRCodeGoogleUrl('gameoc_' . $this->auth->username, $secret, 'googleVerify', ['width' => 2000, 'height' => 2000,'margin'=>10]);
        $qrCodeUrl = 'otpauth://totp/'.'gameoc_' . $this->auth->username.'?secret='.$secret;
        $this->view->assign('qrCodeUrl',$qrCodeUrl);
        return $this->view->fetch();
    }

    /**
     * 更新个人信息
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            $google_switch = $params['google_switch'];
            $params = array_filter(array_intersect_key(
                $params,
                array_flip(array('nickname', 'password', 'avatar'))
            ));
            unset($v);
            if (isset($params['password'])) {
                if (!Validate::is($params['password'], "/^[\S]{6,30}$/")) {
                    $this->error(__("Please input correct password"));
                }
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
            }
            $params['google_switch'] = $google_switch;
            if ($params) {
                if($params['google_switch'] == 1){
                    // 生成谷歌验证秘钥，保存
                    Loader::import('PHPGangsta.GoogleAuthenticator', EXTEND_PATH);
                    $ga = new PHPGangsta_GoogleAuthenticator();
                    $secret = $ga->createSecret();
                    $params['google_verify'] = $secret;
                }
                $admin = Admin::get($this->auth->id);
                $admin->save($params);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                Session::set("admin", $admin->toArray());
                Session::set("admin.safecode", $this->auth->getEncryptSafecode($admin));
                $this->success();
            }
            $this->error();
        }
        return;
    }
}
