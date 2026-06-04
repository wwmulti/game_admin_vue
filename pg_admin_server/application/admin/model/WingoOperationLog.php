<?php

namespace app\admin\model;

use think\Model;


class WingoOperationLog extends Model
{

    

    

    // 表名
    protected $name = 'wingo_operation_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    public function add_data($room_id,$issue,$number,$admin_id)
    {
        $data = [];
        $data['room_id'] = $room_id;
        $data['issue'] = $issue;
        $data['number'] = $number;
        $data['admin_id'] = $admin_id;
        $data['time'] = time();
        self::insert($data);
        return true;
    }
}
