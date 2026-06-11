<?php

namespace app\admin\controller\room;

use app\admin\model\merchant\Merchants;
use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\User;
use app\admin\controller\user\User as UserController;

class WingoLog extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WinGoLog();
    }

    /**
     * 查看
     */
    public function index()
    {
        $room_id = $this->request->get('room_id');
        $issue = $this->request->get('issue');

        list($where, $sort, $order, $offset, $limit) = $this->buildparams([], ['room_id', 'issue']);
        $list = $this->model
            ->where($where)
            ->where('room_id', $room_id)
            ->where('issue', $issue)
            ->where($where)
            ->group('user_id')
            ->order($sort, $order)
            ->paginate($limit);
        $user_ids = [];
        foreach ($list as $vo) {
            $user_ids[] = $vo['user_id'];
        }
        $UserData = User::where('id', 'in', implode(',', $user_ids))->column('id,remote_uid,money');
        foreach ($UserData as &$vu) {
            $vu['remote_uid'] = preg_replace('/[^0-9]/', '', $vu['remote_uid']);
            $vu['money'] = FormatMoney($vu['money']);
        }
        $new_array = [];
        $list_data =  $this->model->where($where)
            ->where('room_id', $room_id)
            ->where('issue', $issue)
            ->where('user_id', 'in', implode(',', $user_ids))
            ->select();
        $ids = [];
        foreach ($list_data as $vo) {
            $ids[] = $vo['user_id'];
            $vo['bet'] = FormatMoney($vo['bet']);
            $new_array[$vo['user_id']]['user_id'] = $vo['user_id'];
            $new_array[$vo['user_id']]['remote_uid'] = $UserData[$vo['user_id']]['remote_uid'] ?? '';
            $new_array[$vo['user_id']]['money'] = $UserData[$vo['user_id']]['money'] ?? '';
            $new_array[$vo['user_id']]['issue'] = $vo['issue'];
            $new_array[$vo['user_id']]['room_id'] = $vo['room_id'];
            $new_array[$vo['user_id']]['sum_bet'] = isset($new_array[$vo['user_id']]['sum_bet']) ? $new_array[$vo['user_id']]['sum_bet'] + $vo['bet'] : $vo['bet'];
            switch ($vo['number']) {
                case 0:
                    $new_array[$vo['user_id']]['zero'] = isset($new_array[$vo['user_id']]['zero']) ? $new_array[$vo['user_id']]['zero'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1:
                    $new_array[$vo['user_id']]['one'] = isset($new_array[$vo['user_id']]['one']) ? $new_array[$vo['user_id']]['one'] + $vo['bet'] : $vo['bet'];
                    break;
                case 2:
                    $new_array[$vo['user_id']]['two'] = isset($new_array[$vo['user_id']]['two']) ? $new_array[$vo['user_id']]['two'] + $vo['bet'] : $vo['bet'];
                    break;
                case 3:
                    $new_array[$vo['user_id']]['three'] = isset($new_array[$vo['user_id']]['three']) ? $new_array[$vo['user_id']]['three'] + $vo['bet'] : $vo['bet'];
                    break;
                case 4:
                    $new_array[$vo['user_id']]['four'] = isset($new_array[$vo['user_id']]['four']) ? $new_array[$vo['user_id']]['four'] + $vo['bet'] : $vo['bet'];
                    break;
                case 5:
                    $new_array[$vo['user_id']]['five'] = isset($new_array[$vo['user_id']]['five']) ? $new_array[$vo['user_id']]['five'] + $vo['bet'] : $vo['bet'];
                    break;
                case 6:
                    $new_array[$vo['user_id']]['six'] = isset($new_array[$vo['user_id']]['six']) ? $new_array[$vo['user_id']]['six'] + $vo['bet'] : $vo['bet'];
                    break;
                case 7:
                    $new_array[$vo['user_id']]['seven'] = isset($new_array[$vo['user_id']]['seven']) ? $new_array[$vo['user_id']]['seven'] + $vo['bet'] : $vo['bet'];
                    break;
                case 8:
                    $new_array[$vo['user_id']]['eight'] = isset($new_array[$vo['user_id']]['eight']) ? $new_array[$vo['user_id']]['eight'] + $vo['bet'] : $vo['bet'];
                    break;
                case 9:
                    $new_array[$vo['user_id']]['nine'] = isset($new_array[$vo['user_id']]['nine']) ? $new_array[$vo['user_id']]['nine'] + $vo['bet'] : $vo['bet'];
                    break;
                case 100:
                    $new_array[$vo['user_id']]['green'] = isset($new_array[$vo['user_id']]['green']) ? $new_array[$vo['user_id']]['green'] + $vo['bet'] : $vo['bet'];
                    break;
                case 101:
                    $new_array[$vo['user_id']]['red'] = isset($new_array[$vo['user_id']]['red']) ? $new_array[$vo['user_id']]['red'] + $vo['bet'] : $vo['bet'];
                    break;
                case 102:
                    $new_array[$vo['user_id']]['purple'] = isset($new_array[$vo['user_id']]['purple']) ? $new_array[$vo['user_id']]['purple'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1000:
                    $new_array[$vo['user_id']]['small'] = isset($new_array[$vo['user_id']]['small']) ? $new_array[$vo['user_id']]['small'] + $vo['bet'] : $vo['bet'];
                    break;
                case 1001:
                    $new_array[$vo['user_id']]['large'] = isset($new_array[$vo['user_id']]['large']) ? $new_array[$vo['user_id']]['large'] + $vo['bet'] : $vo['bet'];
                    break;
            }
            $new_array[$vo['user_id']]['user_id'] = $vo['user_id'];
        }
        UserController::batch_update_balance(implode(',', $ids));
        $room_lates_data = array_values($new_array);
        $result = array("total" => $list->total(), "rows" => $room_lates_data);
        return $this->jsonSucc($result);
    }

    public function update_balance()
    {
        if($this->request->isPost()){
            $row = $this->request->get();
            $user_id = $row['user_id'];
            $this_user = User::where('id',$user_id)->find();
            if($this_user){
                $MerchantsData = Merchants::where('id',$this_user['merchant_id'])->find();
                if($MerchantsData){
                    $post_data = [
                        'UseID'=>$this_user['remote_uid'],
                        'OperatorToken'=>$MerchantsData['token'],
                        'SecretStr'=>$MerchantsData['secret'],
                    ];
                    $return_data = $this->curl($MerchantsData['cash_get_url'],$post_data);
                    if(isset($return_data['data']['balance_amount']) && $return_data){
                        User::where('id',$user_id)->update(['money'=>$return_data['data']['balance_amount']]);
                    }else{
                       return $this->jsonFail('更新失败，接口未返回最新余额');
                    }
                    return $this->jsonSucc();
                }else{
                    return $this->jsonFail('渠道不存在');
                }
            }else{
                return $this->jsonFail('用户不存在');
            }
        }

    }


    /**
     * 请求方法
     */
    public function curl($url = '',  $data = "", $is_json = true)
    {
        $curl_con = curl_init($url);
        $data = json_encode($data);
        curl_setopt($curl_con, CURLOPT_POST, 1);
        curl_setopt($curl_con, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl_con, CURLOPT_HEADER, 0);
        curl_setopt($curl_con, CURLOPT_HTTPHEADER,array(
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
