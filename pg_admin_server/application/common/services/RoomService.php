<?php

namespace app\common\services;

use app\admin\model\game\Type;
use app\admin\model\Room;
use PDOStatement;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Env;
use think\exception\DbException;

class RoomService
{
	/**
	 * 获取游戏列表
	 *
	 * @param $params
	 * @return array
	 * @throws DbException
	 */
	public static function getRoomList($params): array
	{
		$id = $params['id'] ?? null;
		$name = $params['name'] ?? null;
		$typeId = $params['type_id'] ?? null;
		$status = $params['status'] ?? null;
		$type = $params['type'] ?? null;
		$dir = $params['dir'] ?? null;
		$historyTableName = $params['history_table_name'] ?? null;
		$rewardStatus = $params['reward_status'] ?? null;
		$pageSize = $params['pageSize'] ?? 10;
		$list = Room::where(function ($query) use ($id, $name, $typeId, $status, $rewardStatus, $type, $dir, $historyTableName) {
			if ($id) {
				$query->where('id', $id);
			}
			if ($name) {
				$query->where('name', 'like', '%' . $name . '%');
			}
			if ($typeId) {
				$query->where('type_id', $typeId);
			}
			if (!is_null($rewardStatus)) {
				$query->where('reward_status', $rewardStatus);
			}
			if (!is_null($status)) {
				$query->where('status', $status);
			}
			if ($type) {
				$query->where('type', 'like', '%' . $type . '%');
			}
			if ($dir) {
				$query->where('dir', 'like', '%' . $dir . '%');
			}
			if ($historyTableName) {
				$query->where('history_table_name', 'like', '%' . $historyTableName . '%');
			}
		})->order('id', 'desc')
			->paginate($pageSize)
			->toArray();
		$gameTypeArray = [
			1 => '即时',
			2 => '延迟'
		];
		$typeArray = Type::field('id,name')->column('name', 'id');
		foreach ($list['data'] as &$row) {
			if ($row['status'] == 1) {
				$row['status'] = true;
			} else {
				$row['status'] = false;
			}
			if ($row['reward_status'] == 1) {
				$row['reward_status'] = true;
			} else {
				$row['reward_status'] = false;
			}
			$row['game_type_name'] = $typeArray[$row['type_id']] ?? 'UUPG';
			$row['type_name'] = $gameTypeArray[$row['type']] ?? '即时';
		}

		return $list;
	}

	/**
	 * 获取所有房间
	 *
	 * @return Room[]|bool|PDOStatement|string|Collection
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function getAllRoom($params)
	{
		$typeId = $params['type_id'] ?? null;
		return Room::field('id,name')->where(function ($query) use ($typeId) {
			if ($typeId) {
				$query->where('type_id', $typeId);
			}
		})->where('status', 1)
			->select();
	}

	/**
	 * 新增游戏
	 *
	 * @param $params
	 * @return false|int
	 */
	public static function addRoom($params)
	{
		$room = new Room();
		$room->name = $params['name'];
		$room->type_id = $params['type_id'];
		$room->type = $params['type'];
		$room->dir = $params['dir'];
		$room->history_table_name = $params['history_table_name'];
		$room->status = $params['status'];
		return $room->save();
	}

	/**
	 * 编辑游戏
	 *
	 * @param $params
	 * @return false|int
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function editRoom($params)
	{
		$room = Room::where('id', $params['id'])->find();
		$room->name = $params['name'];
		$room->type_id = $params['type_id'];
		$room->type = $params['type'];
		$room->dir = $params['dir'];
		$room->history_table_name = $params['history_table_name'];
		$room->status = $params['status'];
		return $room->save();
	}

	/**
	 * 删除单个游戏
	 *
	 * @param $params
	 * @return void
	 */
	public static function deleteRoom($params)
	{
		Room::where('id', $params['id'])->delete();
	}

	/**
	 * 删除多个游戏
	 *
	 * @param $params
	 * @return void
	 */
	public static function deleteRooms($params)
	{
		$ids = explode(',', $params['ids']);
		Room::whereIn('id', $ids)->delete();
	}

	/**
	 * 修改游戏开关状态
	 *
	 * @return false|int
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function switchRoomStatus($params)
	{
		$room = Room::where('id', $params['id'])->find();
		if ($params['status'] == "true") {
			$room->status = 0;
		} else {
			$room->status = 1;
		}
		return $room->save();
	}

	/**
	 * 修改游戏返奖状态
	 *
	 * @return false|int
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function switchRoomRewardStatus($params)
	{
		$room = Room::where('id', $params['id'])->find();
		if ($params['reward_status'] == "true") {
			$room->reward_status = 0;
		} else {
			$room->reward_status = 1;
		}
		return $room->save();
	}

	/**
	 * 批量修改游戏分类
	 *
	 * @param $params
	 * @return void
	 */
	public static function editRoomsType($params)
	{
		$ids = explode(',', $params['ids']);
		Room::whereIn('id', $ids)->update(['type_id' => $params['type_id']]);
	}

	/**
	 * 刷新游戏配置
	 *
	 * @param $params
	 * @return void
	 */
	public static function refreshSetting($params)
	{
		$roomId = $params['room_id'];
		$url = Env::get('app.game_request', '')."/api/config/update";
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_HEADER, 0);
		//设置获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		//设置post方式提交
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['room_id'=>$roomId]));
		//https
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		//执行命令
		$data = curl_exec($curl);
		//关闭URL请求
		curl_close($curl);
	}

	/**
	 * 获取游戏类型列表
	 *
	 * @param $params
	 * @return array
	 * @throws DbException
	 */
	public static function getTypeList($params): array
	{
		$id = $params['id'] ?? null;
		$name = $params['name'] ?? null;
		$typeSwitch = $params['type_switch'] ?? null;
		$abbreviation = $params['abbreviation'] ?? null;
		$pageSize = $params['pageSize'] ?? 10;
		$list = Type::where(function ($query) use ($id, $name, $typeSwitch, $abbreviation) {
			if ($id) {
				$query->where('id', $id);
			}
			if ($name) {
				$query->where('name', 'like', '%' . $name . '%');
			}
			if (!is_null($typeSwitch)) {
				$query->where('type_switch', $typeSwitch);
			}
			if ($abbreviation) {
				$query->where('abbreviation', 'like', '%' . $abbreviation . '%');
			}
		})->order('id', 'desc')
			->paginate($pageSize)
			->toArray();
		foreach ($list['data'] as &$row) {
			if ($row['type_switch'] == 1) {
				$row['type_switch'] = true;
			} else {
				$row['type_switch'] = false;
			}
			$row['full_url'] = cdnurl($row['image'], true);
		}

		return $list;
	}

	/**
	 * 获取所有游戏类型
	 *
	 * @return Type[]|bool|PDOStatement|string|Collection
	 * @throws DbException
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 */
	public static function getAllType()
	{
		return Type::field('id,name')->where('type_switch', 1)->select();
	}

	/**
	 * 添加类型
	 *
	 * @param $params
	 * @return false|int
	 */
	public static function addType($params)
	{
		$type = new Type();
		$type->name = $params['name'];
		$type->type_switch = $params['type_switch'];
		$type->abbreviation = $params['abbreviation'];
		$type->image = $params['image'];
		return $type->save();
	}

	/**
	 * 编辑类型
	 *
	 * @param $params
	 * @return false|int
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function editType($params)
	{
		$type = Type::where('id', $params['id'])->find();
		$type->name = $params['name'];
		$type->type_switch = $params['type_switch'];
		$type->abbreviation = $params['abbreviation'];
		$type->image = $params['image'];
		return $type->save();
	}

	/**
	 * 切换游戏类型状态
	 *
	 * @param $params
	 * @return void
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function switchType($params)
	{
		$id = $params['id'];
		$type = Type::where('id', $id)->find();
		if ($params['type_switch'] == "true") {
			$type->type_switch = 0;
		} else {
			$type->type_switch = 1;
		}
		$type->save();
	}

	/**
	 * 删除游戏类型
	 *
	 * @param $params
	 * @return void
	 */
	public static function deleteType($params)
	{
		$id = $params['id'];
		Type::where('id', $id)->delete();
	}
}