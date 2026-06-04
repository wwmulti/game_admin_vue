<?php

namespace app\admin\model\merchant;

use think\Model;


class Platform extends Model
{





    // 表名
    protected $name = 'platform';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    public function getSiteAttr()
    {
        $url = $this->switch ? "https://" : "http://";
        return $url . $this->domain;
    }
}
