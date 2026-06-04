<?php
namespace app\common\validate;

use think\Validate;

class Merchant extends Validate
{
	/**
	 * 验证规则
	 */
	protected $rule = [
		'id' => 'require|integer',
		'platform_id' => 'require|integer',
		'name' => 'require|max:20',
		'username' => 'require|max:20',
		'password' => 'require|regex:/\S{6,16}$/',
		'token' => 'require',
		'secret' => 'require',
		'rtp_id' => 'require|integer',
		'cash_get_url' => 'url',
		'cash_transferinout_url' => 'url',
		'style_name_switch' => 'require|in:0,1',
		'style_bb_switch' => 'require|in:0,1',
		'style_ec_switch' => 'require|in:0,1',
		'style_oj_switch' => 'require|in:0,1',
		'official_currency' => 'require|in:BRL,INR,THB',
		'status' => 'require|in:0,1',
		'free_spin_switch' => 'require|in:0,1',
		'ip_array' => 'max:255',
		'm_path' => 'max:255',
		'static_domain' => 'max:255',
		'mark' => 'max:255',
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
			'platform_id', 'name', 'username', 'password', 'token', 'secret', 'rtp_id',
			'cash_get_url', 'cash_transferinout_url',
			'style_name_switch', 'style_bb_switch', 'style_ec_switch', 'style_oj_switch',
			'official_currency', 'status', 'free_spin_switch',
			'ip_array', 'm_path', 'static_domain', 'mark'
		],
		'edit' => [
			'id', 'platform_id', 'name', 'username', 'token', 'secret', 'rtp_id',
			'cash_get_url', 'cash_transferinout_url',
			'style_name_switch', 'style_bb_switch', 'style_ec_switch', 'style_oj_switch',
			'official_currency', 'status', 'free_spin_switch',
			'ip_array', 'm_path', 'static_domain', 'mark'
		]
	];

}