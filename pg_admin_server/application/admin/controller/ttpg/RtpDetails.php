<?php

namespace app\admin\controller\ttpg;

use app\admin\model\AuthGroupAccess;
use app\admin\model\BetAmountConfig;
use app\admin\model\Room;
use app\common\controller\Backend;
use think\Db;
use app\admin\model\RtpGroupConfig;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\RoomGroupConfig;
use app\admin\model\merchant\Merchants;

class RtpDetails extends Backend
{

    protected $noNeedRight = ['rtp_list'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RtpGroupConfig;
        $MerchantsData = Merchants::column('id,name');
        $this->view->assign('MerchantsData', $MerchantsData);
    }

    public function rtp_list()
    {
        $MerchantsData = \app\admin\model\Rtp::column('id,rtp');
        return json($MerchantsData);
    }

    /**
     * 查看
     */
    public function index($ids = null)
    {
        $get = $this->request->get();
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $room_name = Room::where('id',$get['room_id'])->value('name');
            $list = $this->model
                ->where('room_id',$get['room_id'])
                ->where('rtp_id',$get['rtp_id'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $MerchantsData = \app\admin\model\Rtp::column('id,rtp');
            foreach ($list as &$row) {
                $row['id'] = $row['room_id'].':'.$row['bet_amount'];
                $row['bet_amount'] = FormatMoney($row['bet_amount']);
                $row['name'] = $room_name;
                $row['rtp_ids'] = $row['rtp_id'];
                $row['rtp_id'] = $MerchantsData[$row['rtp_id']]??'';
                $row['reward_id'] = (string)$row['reward_id'];
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $this->assign('room_id',$get['room_id']);
        $this->assign('rtp_id',$get['rtp_id']);
        $RoomGroupConfig = RoomGroupConfig::where('room_id',$get['room_id'])->field('id value,name text')->select();
        foreach ($RoomGroupConfig as &$vo){
            $vo['value'] = (string)$vo['value'];
        }
        $this->assign('RoomGroupConfig',json_encode($RoomGroupConfig));
        return $this->view->fetch();
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add($ids = null)
    {
        $get = $this->request->get();
        if (false === $this->request->isPost()) {
            $RoomGroupConfig = collection(RoomGroupConfig::where('room_id',$get['room_id'])->column('name','id'))->toArray();
            $this->assign('RoomGroupConfig',$RoomGroupConfig);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        $result = false;
        Db::startTrans();
        try {
            $RoomGroupConfig = RoomGroupConfig::where('room_id',$get['room_id'])->column('group_ids','id');
            $add_data = [
                'room_id'=>$get['room_id'],
                'rtp_id'=>$get['rtp_id'],
                'bet_amount'=>WriteMoney($params['bet_amount']),
                'reward_id'=>$params['reward_id'],
                'group_id'=>$RoomGroupConfig[$params['reward_id']],
            ];
            $result = $this->model->insert($add_data);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }
    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $get = $this->request->get();
        if (false === $this->request->isPost()) {
            $where['bet_amount'] = ['eq',WriteMoney($get['bet_amount'])];
            $where['room_id'] = ['eq',$get['room_id']];
            $row = $this->model->where($where)->find();
            $row['old_bet_amount'] = $row['bet_amount'];
            $row['bet_amount'] = FormatMoney($row['bet_amount']);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $result = false;
        Db::startTrans();
        try {
            $where['room_id'] = $get['room_id'];
            $where['rtp_id'] = $get['rtp_id'];
            $where['bet_amount'] = WriteMoney($get['bet_amount']);
            $update_data['bet_amount'] = WriteMoney($params['bet_amount']);
            $result = $this->model->where($where)->update($update_data);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function edit_update($ids = NULL)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $RoomGroupConfig = RoomGroupConfig::where('room_id',$params['room_id'])->column('group_ids','id');
            $where['room_id'] = ['eq',$params['room_id']];
            $where['bet_amount'] = ['eq',WriteMoney($params['bet_amount'])];
            $update_data['reward_id'] = $params['reward_id'];
            $update_data['group_id'] = $RoomGroupConfig[$params['reward_id']];
            $this->model->where($where)->update($update_data);
            $this->success();
        }
    }

    public function batch_updates($ids)
    {
        $get = $this->request->get();
        if (false === $this->request->isPost()) {
            $RoomGroupConfig = RoomGroupConfig::where('room_id',$get['room_id'])->field('id,name')->select();
            $this->assign('RoomGroupConfig',json_encode($RoomGroupConfig));
            $this->view->assign('list', $RoomGroupConfig);
            return $this->view->fetch();
        } else {
            $params = $this->request->post('row/a');
            $RoomGroupConfig = RoomGroupConfig::where('id',$params['reward_id'])->value('group_ids');
            $ids_array = explode(',',$get['ids']);
            foreach ($ids_array as $vo){
                $where['room_id'] = ['eq',$get['room_id']];
                $where['rtp_id'] = ['eq',$get['rtp_id']];
                $where['bet_amount'] = ['eq',WriteMoney($vo)];
                $update_data['reward_id'] = $params['reward_id'];
                $update_data['group_id'] = $RoomGroupConfig;
                $this->model->where($where)->update($update_data);
            }
            $this->success('配置成功');
        }
    }
    public function batch_merchant($ids)
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        } else {
            $params = $this->request->post('row/a');
            $ids_array = explode(',',$ids);
            foreach ($ids_array as $vo){
                $where_array = explode(':',$vo);
                $where['room_id'] = ['eq',$where_array[0]];
                $where['bet_amount'] = ['eq',$where_array[1]];
                $update_data['rtp_id'] = $params['rtp_id'];
                $this->model->where($where)->update($update_data);
            }
            $this->success('配置成功');
        }
    }
    public function batch_config()
    {
        $get = $this->request->get();
        if (false === $this->request->isPost()) {
            $RoomGroupConfig = RoomGroupConfig::where('room_id',$get['room_id'])->field('id,name')->select();
            $this->assign('RoomGroupConfig',json_encode($RoomGroupConfig));
            $this->view->assign('list', $RoomGroupConfig);
            return $this->view->fetch();
        } else {
            $params = $this->request->post('row/a');
            $RoomGroupConfig = RoomGroupConfig::where('id',$params['reward_id'])->value('group_ids');
            $this->model->where('room_id',$get['room_id'])
                ->where('rtp_id',$get['rtp_id'])
                ->delete();
            $BetAmountConfig = BetAmountConfig::where('room_id',$get['room_id'])->select();
            $add_data = [];
            foreach ($BetAmountConfig as $vo){
                $add_data[] = [
                    'room_id'=>$get['room_id'],
                    'rtp_id'=>$get['rtp_id'],
                    'bet_amount'=>$vo['bet_amount'],
                    'reward_id'=>$params['reward_id'],
                    'group_id'=>$RoomGroupConfig,
                ];
            }
            $this->model->insertAll($add_data);
            $this->success('配置成功');
        }
    }
}
