<?php

namespace app\admin\controller\merchant;


use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\BetGroupConfig;
use app\admin\model\Room;
use app\admin\model\RoomGroupConfig;
use app\common\controller\Backend;
use app\admin\model\merchant\Platform;
use fast\Random;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Session;
use think\Validate;
use app\admin\model\BetAmountConfig;
use app\admin\model\merchant\Merchants;

/**
 * 管理员管理
 *
 * @icon fa fa-circle-o
 */
class Setting extends Backend
{

    /**
     * Settings模型对象
     * @var \app\admin\model\merchant\Setting
     */
    protected $model = null;
    protected $platformData = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\merchant\Setting;

    }


    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $AuthGroupAccessStatus = 1;
        $this_admin_access = AuthGroupAccess::where('uid', $this->auth->id)->value('group_id');
        if($this_admin_access == 2){
            $AuthGroupAccessStatus = 2;
            $row = Merchants::where('id',$ids)->where('platform_id',$this->auth->platform_id)->find();
        }else if($this_admin_access == 3){
            $AuthGroupAccessStatus = 2;
            $row = Merchants::where('id',$ids)
                ->where('platform_id',$this->auth->platform_id)
                ->where('id',$this->auth->merchant_id)
                ->find();

        }else{
            $row = Merchants::where('id',$ids)->find();
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

            $setting = $this->model->where('id',$row->id)->field('id,conf')->find();
            if(!$setting){
                $data  = [
                    'max_reward' =>  0,
                ];
                $insert = [
                    'id'=>$row['id'],
                    'conf' => json_encode($data),
                ];
                $this->model->save($insert);
                $setting = $this->model->where('id',$row->id)->field('id,conf')->find();
            }
            
            $this->view->assign('row', json_decode($setting->conf,true));
         
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        $max_reward = (int)$params['max_reward'];
        if($max_reward < 0 || !is_numeric($params['max_reward'])){
            $this->error('请填入正确的数字，且必须大于等于0');
        }

        $data  = [
            'max_reward' =>  $max_reward,
        ];
        $update = [
            'conf' => json_encode($data),
        ];

 
        Db::startTrans();
        try {
            $this->model->where('id',$row['id'])->update($update);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $room = new \app\admin\controller\room\Room(); 
        $room->api_post();
        $this->success();
    }



  

}
