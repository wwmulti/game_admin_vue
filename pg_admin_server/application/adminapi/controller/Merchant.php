<?php

namespace app\adminapi\controller;

use app\admin\model\Admin;
use app\common\services\MerchantService;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\response\Json;

class Merchant extends MainController
{
	/**
	 * 获取商户列表
	 *
	 * @return Json
	 * @throws DbException
	 */
	public function getMerchantList(): Json
	{

		$params = request()->param();
		$list = MerchantService::getMerchantList($params);
		return $this->jsonSucc($list);
	}

	/**
	 * 获取所有的商户列表
	 *
	 * @return Json
	 */
	public function getAllMerchant(): Json
	{
		try {
			$result = MerchantService::getAllMerchant();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取商户详情
	 *
	 * @return Json
	 */
	public function getMerchant(): Json
	{
		$id = request()->param('id');
		try {
			$result = MerchantService::getMerchant($id);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 添加商户列表
	 *
	 * @return Json
	 * @throws DbException
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 */
	public function addMerchant(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Merchant();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		$roomData = $params['room_data'] ?? null;
		// 判断账号是否存在
		$thisAdmin = Admin::where('username', $params['username'])->find();
		if ($thisAdmin) {
			$this->jsonFail(__('Username is exist'));
		}
		Db::startTrans();
		try {
			MerchantService::addMerchant($params, $roomData);
			Db::commit();
		} catch (\Exception $e) {
			Db::rollback();
			$this->jsonFail($e->getMessage());
		}
		return $this->jsonSucc();
	}

	/**
	 * 编辑商户
	 *
	 * @return Json
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public function editMerchant(): Json
	{
		$params = request()->param();
		$adminUser = $this->getAdmin();
		$validate = new \app\common\validate\Merchant();
		if (!$validate->scene('edit')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			MerchantService::editMerchant($adminUser, $params);
			Db::commit();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
		return $this->jsonSucc();
	}

	/**
	 * 删除商户
	 *
	 * @return Json
	 */
	public function deleteMerchant(): Json
	{
		$id = request()->param('id');
		Db::startTrans();
		try {
			MerchantService::deleteMerchant($id);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 添加时获取roomData
	 *
	 * @return Json
	 */
	public function getAddRoomData(): Json
	{
		Db::startTrans();
		try {
			$result = MerchantService::getAddRoomData();
			Db::commit();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑时获取roomData
	 *
	 * @return Json
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public function getEditRoomData(): Json
	{
		$id = request()->param('id');
		$adminUser = $this->getAdmin();
		Db::startTrans();
		try {
			$result = MerchantService::getEditRoomData($adminUser, $id);
			Db::commit();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}
}