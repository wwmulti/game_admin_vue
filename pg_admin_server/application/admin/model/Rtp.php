<?php

namespace app\admin\model;

use think\Model;


class Rtp extends Model
{

    

    

    // 表名
    protected $name = 'rtp';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    protected function base($query)
    {
        // 添加默认查询条件
        $query->where('status', 1);
    }







}
