<?php

namespace app\common\services;

use fast\Random;

class CommonService
{
	/**
	 * 创建salt
	 *
	 * @return string
	 */
	public static function createSalt(): string
	{
		return Random::alnum();
	}

	/**
	 * 生成密码
	 *
	 * @param $password
	 * @param string $salt
	 * @return string
	 */
	public static function getEncryptPassword($password, string $salt = ''): string
	{
		return md5(md5($password) . $salt);
	}

	/**
	 * 金币输出显示
	 */
	public static function formatMoney($num, $multiple = 1000, $scale = 2): ?string
	{
		return bcdiv($num, $multiple, $scale);
	}

	/**
	 * 金币写入值
	 */
	public static function writeMoney($num, $multiple = 1000): string
	{
		return bcmul($num, $multiple);
	}
}