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
        return json($MerchantsData);
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
            $filter = $this->request->get("filter", '');
            $filter = (array)json_decode($filter, true);
            $merchant_id = "";
            if(isset($filter['merchant_id'])){
                $merchant_id = $filter['merchant_id'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where('reward_status',1)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as &$row) {
                $RoomGroupConfig = RoomGroupConfig::where('room_id',$row['id'])->column('name','id');
                $reward_data = BetGroupConfig::where('room_id',$row['id'])
                    ->where('merchant_id',$merchant_id)
                    ->field('reward_id')->select();
                $reward_id = [];
                foreach ($reward_data as $value){
                    $reward_id[] = $RoomGroupConfig[$value['reward_id']]??'';
                }
                $reward_id = implode(',',$reward_id);
                $row->hidden(['status','dir','history_table_name','type','reward_status','type_id']);
                $row['merchant_id'] = $merchant_id;
                $row['reward_id'] = $reward_id;
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }

}
