<?php

namespace app\admin\controller\user;

use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Merchants;
use app\admin\model\merchant\Platform;
use app\common\controller\Backend;
use app\admin\model\User as UserModel;
use think\cache;

/**
 * 玩家管理
 *
 * @icon fa fa-users
 */
class User extends Backend
{

    protected $noNeedRight = ['platform_name', 'merchant_name'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;

    }


    public function platform_name()
    {
        $where_array = [];
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if ($this_admin_access == 2) {
            $where_array['id'] = ['eq', $this->auth->platform_id];
        } else if ($this_admin_access == 3) {
            return [];
        }
        $platform_data = Platform::where($where_array)->column('id,name');
        return json($platform_data);
    }


    public function merchant_name()
    {
        $where_array = [];
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if ($this_admin_access == 2) {
            $where_array['platform_id'] = ['eq', $this->auth->platform_id];
        } else if ($this_admin_access == 3) {
            return [];
        }
        $platform_data = Merchants::where($where_array)->column('id,name');
        return json($platform_data);
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get("filter"),true);
            if(isset($filter['money'])){
                $gold = explode(',',$filter['money']);
                if(count($gold) == 2){
                    $filter['money'] = ($gold[0]*1000).','.($gold[1]*1000);
                    $this->request->get(["filter"=>json_encode($filter)]);
                }
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where_array = [];
            $MerchantArray = Merchants::column('name', 'id');
            $PlatformArray = Platform::column('name', 'id');
            $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
            if ($this_admin_access == 2) {
                $where_array['platform_id'] = ['eq', $this->auth->platform_id];
            } else if ($this_admin_access == 3) {
                $where_array['merchant_id'] = ['eq', $this->auth->merchant_id];
            }
            $list = $this->model
                ->where($where)
                ->where($where_array)
                ->order($sort, $order)
                ->paginate($limit);
            $ids = [];
            foreach ($list as &$row) {
                $ids[] =  $row['id'];
                $row->visible(['id','platform_id', 'merchant_id', 'remote_uid', 'name', 'created_at', 'money']);
                $row['platform_id'] = $PlatformArray[$row['platform_id']] ?? '';
                $row['merchant_id'] = $MerchantArray[$row['merchant_id']] ?? '';
                $row['money'] = FormatMoney($row['money']);
            }
            $this->batch_update_balance(implode(',',$ids));
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
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

    public function update_balance()
    {
        if ($this->request->isPost()) {
            $row = $this->request->get();
            $user_id = $row['user_id'];
            $this_user = $this->model->where('id', $user_id)->find();
            if ($this_user) {
                $MerchantsData = Merchants::where('id', $this_user['merchant_id'])->find();
                if ($MerchantsData) {
                    $post_data = [
                        'UseID' => $this_user['remote_uid'],
                        'OperatorToken' => $MerchantsData['token'],
                        'SecretStr' => $MerchantsData['secret'],
                    ];
                    $return_data = $this->curl($MerchantsData['cash_get_url'], $post_data);
                    if (isset($return_data['data']['balance_amount']) && $return_data) {
                        $this->model->where('id', $user_id)->update(['money' => $return_data['data']['balance_amount']]);
                    } else {
                        $this->error('更新失败，接口未返回最新余额');
                    }
                    $this->success();
                } else {
                    $this->error('渠道不存在');
                }
            } else {
                $this->error('用户不存在');
            }
        }
    }

    public static function batch_update_balance($ids)
    {
        $user_update_cache = Cache::get('user_update_cache');
        if(empty($user_update_cache)){
            $UserLise = UserModel::where('id', 'in',$ids)->field('id,merchant_id,remote_uid')->select();
            $MerchantsData = Merchants::column('id,token,secret,cash_get_url');
            foreach ($UserLise as $vo){
                $post_data = [
                    'UseID' => $vo['remote_uid'],
                    'OperatorToken' => $MerchantsData[$vo['merchant_id']]['token'],
                    'SecretStr' => $MerchantsData[$vo['merchant_id']]['secret'],
                ];
                if(!empty($MerchantsData[$vo['merchant_id']]['cash_get_url'])){
                    $return_data = self::curl($MerchantsData[$vo['merchant_id']]['cash_get_url'], $post_data);
                    if (isset($return_data['data']['balance_amount']) && $return_data) {
                        UserModel::where('id', $vo['id'])->update(['money' => $return_data['data']['balance_amount']]);
                    }
                }
            }
            Cache::set('user_update_cache',date('Y-m-d H:i:s'),30);
        }
    }





    /**
     * 请求方法
     */
    public static function curl($url = '', $data = "", $is_json = true)
    {
        $curl_con = curl_init($url);
        $data = json_encode($data);
        curl_setopt($curl_con, CURLOPT_POST, 1);
        curl_setopt($curl_con, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_con, CURLOPT_HEADER, 0);
        curl_setopt($curl_con, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl_con, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl_con);
        $status = curl_getinfo($curl_con);
        curl_close($curl_con);

        if (isset($status['http_code']) && $status['http_code'] == 200) {
            return $is_json ? json_decode($res, true) : $res;
        } else {
            return FALSE;
        }
    }
}
