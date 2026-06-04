<?php
namespace app\common\validate;

use think\Validate;

class Rtp extends Validate
{
	/**
	 * 验证规则
	 */
	protected $rule = [
		'id' => 'require|integer',
		'rtp' => 'require|number',
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
			'rtp'
		],
		'edit' => [
			'id', 'rtp'
		]
	];

}