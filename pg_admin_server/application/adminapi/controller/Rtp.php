<?php

namespace app\adminapi\controller;

use app\common\services\RtpService;
use think\Db;
use think\exception\DbException;
use think\response\Json;

class Rtp extends MainController
{

	/**
	 * 获取所有的rtp列表
	 *
	 * @return Json
	 */
	public function getRtpList(): Json
	{
		$params = request()->param();
		try {
			$list = RtpService::getRtpList($params);
		} catch (DbException $e) {
			return $this->jsonFail($e->getMessage());
		}
		return $this->jsonSucc($list);
	}

	/**
	 * 获取所有的rtp
	 *
	 * @return Json
	 */
	public function getAllRtp(): Json
	{
		Db::startTrans();
		try {
			$list = RtpService::getAllRtp();
			Db::commit();
			return $this->jsonSucc($list);
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 新增rtp
	 *
	 * @return Json
	 */
	public function addRtp(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Rtp();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			RtpService::addRtp($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑rtp
	 *
	 * @return Json
	 */
	public function editRtp(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Rtp();
		if (!$validate->scene('edit')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			RtpService::editRtp($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除rtp
	 *
	 * @return Json
	 */
	public function deleteRtp(): Json
	{
		$params = request()->param();
		Db::startTrans();
		try {
			RtpService::deleteRtp($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}
}