<?php

namespace app\admin\controller\ttpg;

use app\admin\model\merchant\Merchants;
use app\admin\model\RoomGroupConfig;
use app\common\controller\Backend;
use app\admin\model\BetGroupConfig;

class Ttpg extends Backend
{

    protected $noNeedRight = ['merchant_name'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Room;
    }

    public function merchant_name()
    {
        $MerchantsData = Merchants::column('id,name');
        return $this->jsonSucc($MerchantsData);
    }

    /**
     * 查看
     */
    public function index()
    {
        $merchant_id = $this->request->get('merchant_id', '');
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $list = $this->model
            ->where('reward_status', 1)
            ->order($sort, $order)
            ->paginate($limit);
        $MerchantArray = Merchants::column('name', 'id');
        foreach ($list as &$row) {
            $RoomGroupConfig = RoomGroupConfig::where('room_id', $row['id'])->column('name', 'id');
            $reward_data = BetGroupConfig::where('room_id', $row['id'])
                ->where('merchant_id', $merchant_id)
                ->field('reward_id')->select();
            $reward_id = [];
            foreach ($reward_data as $value) {
                $reward_id[] = $RoomGroupConfig[$value['reward_id']] ?? '';
            }
            $reward_id = implode(',', $reward_id);
            $row->hidden(['status', 'dir', 'history_table_name', 'type', 'reward_status', 'type_id']);
            $row['merchant_id'] = $merchant_id;
            $row['reward_id'] = $reward_id;
        }
        $result = [
            'total' => $list->total(),
            'rows' => $list->items(),
            'merchant_name' => $MerchantArray,
        ];
        return $this->jsonSucc($result);
    }

}
