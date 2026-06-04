<?php
namespace app\common\validate;

use think\Validate;

class Platform extends Validate
{
	/**
	 * 验证规则
	 */
	protected $rule = [
		'id' => 'require|integer',
		'name' => 'require|max:20',
		'username' => 'require|max:20',
		'password' => 'require|regex:/\S{6,16}$/',
		'status' => 'require|in:0,1'
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
			'name', 'username', 'password', 'status'
		],
		'edit' => [
			'id', 'name', 'username', 'password', 'status'
		]
	];

}