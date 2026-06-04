<?php
namespace app\common\validate;

use think\Validate;

class Type extends Validate
{
	/**
	 * 验证规则
	 */
	protected $rule = [
		'id' => 'require|integer',
		'name' => 'require',
		'type_switch' => 'require|in:0,1'
	];
	/**
	 * 提示消息
	 */
	protected $message = [

	];
	/**
	 * 验证场景
	 */
	protected $scene = [
		'add'  => [
			'name', 'type_switch'
		],
		'edit' => [
			'id', 'name', 'type_switch'
		]
	];

}