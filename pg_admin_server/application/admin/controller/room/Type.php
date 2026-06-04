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


}
