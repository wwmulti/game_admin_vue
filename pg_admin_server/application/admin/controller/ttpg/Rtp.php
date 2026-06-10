<?php

namespace app\admin\controller\ttpg;

use app\admin\model\RoomGroupConfig;
use app\common\controller\Backend;
use app\admin\model\RtpGroupConfig;

class Rtp extends Backend
{

    protected $noNeedRight = ['rtp_list'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Room;
    }

    public function rtp_list()
    {
        $MerchantsData = \app\admin\model\Rtp::column('id,rtp');
        return $MerchantsData;
    }

    /**
     * 查看
     */
    public function index()
    {
        $filter = $this->request->get();
        $rtp_id = "";
        if (isset($filter['rtp_id'])) {
            $rtp_id = $filter['rtp_id'];
        }
        list($where, $sort, $order, $offset, $limit) = $this->buildparams([], ['rtp_id']);
        $list = $this->model
            ->where('reward_status', 1)
            ->order($sort, $order)
            ->paginate($limit);
            
        foreach ($list as &$row) {
            $RoomGroupConfig = RoomGroupConfig::where('room_id', $row['id'])->column('name', 'id');
            $reward_data = RtpGroupConfig::where('room_id', $row['id'])
                ->where('rtp_id', $rtp_id)
                ->field('reward_id')->select();
            $reward_id = [];
            foreach ($reward_data as $value) {
                $reward_id[] = $RoomGroupConfig[$value['reward_id']] ?? '';
            }
            $reward_id = implode(',', $reward_id);
            $row->hidden(['status', 'dir', 'history_table_name', 'type', 'reward_status', 'type_id']);
            $row['rtp_id'] = $rtp_id;
            $row['reward_id'] = $reward_id;
        }
        $result = [
            "total" => $list->total(), 
            "rows" => $list->items(),
            "rtp_list" => $this->rtp_list(),
        ];
        return $this->jsonSucc($result);
    }

}
