<?php

namespace app\adminapi\controller;

use app\common\services\PlatformService;
use think\Db;
use think\exception\DbException;
use think\response\Json;

class Platform extends MainController
{
	/**
	 * 平台用户列表
	 *
	 * @return Json
	 * @throws DbException
	 */
	public function getPlatformList(): Json
	{
		$adminUser = $this->getAdmin();
		$params = request()->param();
		$list = PlatformService::getPlatformList($adminUser, $params);
		return $this->jsonSucc($list);
	}

	/**
	 * 获取所有的平台用户
	 *
	 * @return Json
	 */
	public function getAllPlatform(): Json
	{
		$list = PlatformService::getAllPlatform();
		return $this->jsonSucc($list);
	}

	/**
	 * 创建用户
	 *
	 * @return Json
	 */
	public function addPlatform(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Platform();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			PlatformService::addPlatform($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑平台用户
	 *
	 * @return Json
	 */
	public function editPlatform(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Platform();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			PlatformService::editPlatform($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除平台用户
	 *
	 * @return Json
	 */
	public function deletePlatform(): Json
	{
		$params = request()->param();
		$id = $params['id'] ?? null;
		Db::startTrans();
		try {
			PlatformService::deletePlatform($id);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}
}