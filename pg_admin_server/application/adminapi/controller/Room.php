<?php

namespace app\adminapi\controller;

use app\common\services\RoomService;
use app\common\services\WinRoomService;
use think\Db;
use think\response\Json;

class Room extends MainController
{
	/**
	 * 获取游戏列表
	 *
	 * @return Json
	 */
	public function getRoomList(): Json
	{
		$params = request()->param();
		try {
			$result = RoomService::getRoomList($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取所有room
	 *
	 * @return Json
	 */
	public function getAllRoom(): Json
	{
		$params = request()->param();
		try {
			$result = RoomService::getAllRoom($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 新增游戏
	 *
	 * @return Json
	 */
	public function addRoom(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Room();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		try {
			RoomService::addRoom($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑游戏
	 *
	 * @return Json
	 */
	public function editRoom(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Room();
		if (!$validate->scene('edit')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		try {
			RoomService::editRoom($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除游戏
	 *
	 * @return Json
	 */
	public function deleteRoom(): Json
	{
		$params = request()->param();
		try {
			RoomService::deleteRoom($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除多个游戏
	 *
	 * @return Json
	 */
	public function deleteRooms(): Json
	{
		$params = request()->param();
		try {
			RoomService::deleteRooms($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 切换游戏开关
	 *
	 * @return Json
	 */
	public function switchRoomStatus(): Json
	{
		$params = request()->param();
		try {
			RoomService::switchRoomStatus($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 切换游戏返奖开关
	 *
	 * @return Json
	 */
	public function switchRoomRewardStatus(): Json
	{
		$params = request()->param();
		try {
			RoomService::switchRoomRewardStatus($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 批量修改游戏分类
	 *
	 * @return Json
	 */
	public function editRoomsType(): Json
	{
		$params = request()->param();
		try {
			RoomService::editRoomsType($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 刷新游戏配置
	 *
	 * @return Json
	 */
	public function refreshSetting(): Json
	{
		$params = request()->param();
		try {
			RoomService::refreshSetting($params);
			return $this->jsonSucc();
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取游戏类型列表
	 *
	 * @return Json
	 */
	public function getTypeList(): Json
	{
		$params = request()->param();
		try {
			$result = RoomService::getTypeList($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取所有游戏类型
	 *
	 * @return Json
	 */
	public function getAllType(): Json
	{
		try {
			$result = RoomService::getAllType();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 新增游戏类型
	 *
	 * @return Json
	 */
	public function addType(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Type();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			RoomService::addType($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑游戏类型
	 *
	 * @return Json
	 */
	public function editType(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\Type();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			RoomService::editType($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 切换游戏分类开关
	 *
	 * @return Json
	 */
	public function switchType(): Json
	{
		$params = request()->param();
		Db::startTrans();
		try {
			RoomService::switchType($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除游戏类型
	 *
	 * @return Json
	 */
	public function deleteType(): Json
	{
		$params = request()->param();
		Db::startTrans();
		try {
			RoomService::deleteType($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取 winning go 设置列表
	 *
	 * @return Json
	 */
	public function winRoom(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::getWinRoomList($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 添加 winning go 设置
	 *
	 * @return Json
	 */
	public function addWinGoSet(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\WinGo();
		if (!$validate->scene('add')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			WinRoomService::addWinGoSet($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 编辑 winning go 设置
	 *
	 * @return Json
	 */
	public function editWinGoSet(): Json
	{
		$params = request()->param();
		$validate = new \app\common\validate\WinGo();
		if (!$validate->scene('edit')->check($params)) {
			return $this->jsonFail($validate->getError());
		}
		Db::startTrans();
		try {
			WinRoomService::editWinGoSet($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 切换 winning go 设置
	 *
	 * @return Json
	 */
	public function winGoSetSwitch(): Json
	{
		$params = request()->param();
		Db::startTrans();
		try {
			WinRoomService::winGoSetSwitch($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 删除 winning go 设置
	 *
	 * @return Json
	 */
	public function delWinGoSet(): Json
	{
		$params = request()->param();
		Db::startTrans();
		try {
			WinRoomService::delWinGoSet($params);
			Db::commit();
			return $this->jsonSucc();
		} catch (\Exception $e) {
			Db::rollback();
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取 winning go 操作日志列表
	 *
	 * @return Json
	 */
	public function winGoOperationLog(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::winGoOperationLog($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取 winning go 房间列表
	 *
	 * @return Json
	 */
	public function wingoRoom(): Json
	{
		try {
			$result = WinRoomService::wingoRoom();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取 winning go 房间名称列表
	 *
	 * @return Json
	 */
	public function roomName(): Json
	{
		try {
			$result = WinRoomService::roomName();
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取房间总统计
	 *
	 * @return Json
	 */
	public function latestData(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::latestData($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 房间详细信息
	 *
	 * @return Json
	 */
	public function details(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::details($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}


	/**
	 * 预设值开奖信息
	 *
	 * @return Json
	 */
	public function winningSubmit(): Json
	{
		if (!$this->request->isPost()) {
			return $this->jsonFail(__('Incorrect request method'));
		}

		$params = request()->param();
		try {
			$params['auth_id'] = $this->auth->id;
			$result = WinRoomService::winningSubmit($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 获取 winning go 预估记录列表
	 *
	 * @return Json
	 */
	public function wingoForecastLogs(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::wingoForecastLogs($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * wingo单局下注详情
	 *
	 * @return Json
	 */
	public function wingoLogs(): Json
	{
		$params = request()->param();
		try {
			$result = WinRoomService::wingoLogs($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}

	/**
	 * 更新余额
	 *
	 * @return Json
	 */
	public function updateBalance(): Json
	{
		if (!$this->request->isPost()) {
			return $this->jsonFail(__('Incorrect request method'));
		}

		$params = request()->param();
		try {
			$result = WinRoomService::updateBalance($params);
			return $this->jsonSucc($result);
		} catch (\Exception $e) {
			return $this->jsonFail($e->getMessage());
		}
	}
}