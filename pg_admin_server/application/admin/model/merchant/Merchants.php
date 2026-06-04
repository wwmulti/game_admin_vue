<?php

namespace app\admin\model\merchant;

use app\merchant\model\MerchantAuthRule;
use think\Model;


class Merchants extends Model
{

    

    

    // 表名
    protected $name = 'merchants';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;

}
