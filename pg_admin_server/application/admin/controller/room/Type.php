<?php

namespace app\admin\controller\room;

use app\common\controller\Backend;

class Type extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\game\Type;
    }

    public function index()
    {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams(['name' => 'like', 'abbreviation' => 'like']);
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return $this->jsonSucc($result);
    }

}
