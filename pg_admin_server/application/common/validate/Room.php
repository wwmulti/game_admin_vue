<?php
namespace app\common\validate;

use think\Validate;

class Room extends Validate
{
	/**
	 * 验证规则
	 */
	protected $rule = [
		'id' => 'require|integer',
		'name' => 'require',
		'type_id' => 'require|integer',
		'history_table_name' => 'require',
		'status' => 'require|in:0,1',
		'type' => 'require|in:1,2'
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
			'name', 'type_id', 'history_table_name', 'status', 'type'
		],
		'edit' => [
			'id', 'name', 'type_id', 'history_table_name', 'status', 'type'
		]
	];

}