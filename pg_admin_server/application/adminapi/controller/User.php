<?php

namespace app\adminapi\controller;

use app\common\services\UserService;
use think\response\Json;

class User extends MainController
{
	/**
	 * 获取用户列表
	 *
	 * @return Json
	 */
	public function getUserList(): Json
	{
		$params = request()->param();
		try {
			$result = UserService::getUserList($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取用户余额
	 *
	 * @return Json
	 */
	public function getUserMoney(): Json
	{
		$params = request()->param();
		$id = $params['id'];
		try {
			$result = UserService::getUserMoney($id);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取游戏历史记录
	 *
	 * @return Json
	 */
	public function getUserGameHistoryList(): Json
	{
		$params = request()->param();
		try {
			$result = UserService::getUserGameHistoryList($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}
}