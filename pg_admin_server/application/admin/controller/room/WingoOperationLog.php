<?php

namespace app\admin\controller\room;

use app\common\controller\Backend;

class WingoOperationLog extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WingoOperationLog;

    }
    public function index()
    {
        list($where, $sort, $order, $offset, $limit) = $this->buildparams(['time' => 'times']);
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        foreach ($list as $row) {
            $row->visible(['id', 'admin_id', 'room_id', 'issue', 'number', 'time']);
        }
        $result = array("total" => $list->total(), "rows" => $list->items());
        return $this->jsonSucc($result);
    }

}
