<?php

namespace app\common\validate;

use think\Validate;

class WinGo extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'id' => 'require|integer',
        'seconds' => 'require|integer',
        'stop_second' => 'require|integer',
        'odds' => 'require|integer',
        'weight' => 'require|integer',
        'status' => 'require|in:0,1'
    ];
    /**
     * 提示消息
     */
    protected $message = [];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [
            'seconds',
            'stop_second',
            'odds',
            'weight',
            'status'
        ],

        'edit' => [
            'id',
            'seconds',
            'stop_second',
            'odds',
            'weight',
            'status'
        ]
    ];
}
