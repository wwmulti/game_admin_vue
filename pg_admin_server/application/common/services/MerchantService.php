<?php

namespace app\common\services;

use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\BetAmountConfig;
use app\admin\model\BetGroupConfig;
use app\admin\model\merchant\Merchants;
use app\admin\model\Room;
use app\admin\model\RoomGroupConfig;
use fast\Random;
use PDOStatement;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Model;
use think\Paginator;

class MerchantService
{
	/**
	 * 获取商户列表
	 *
	 * @param $params
	 * @return Paginator
	 * @throws DbException
	 */
	public static function getMerchantList($params): Paginator
	{
		$pageSize = $params['pageSize'] ?? 10;
		$platformId = $params['platform_id'] ?? null;
		$id = $params['id'] ?? null;
		$name = $params['name'] ?? null;
		return Merchants::alias('m')
			->field('m.*,a.username')
			->join('admin a', 'a.id = m.admin_id', 'left')
			->where(function ($query) use ($platformId) {
				if ($platformId) {
					$query->where('m.platform_id', $platformId);
				}
			})
			->where(function ($query) use ($id) {
				if ($id) {
					$query->where('m.id', $id);
				}
			})
			->where(function ($query) use ($name) {
				if ($name) {
					$query->where('m.name', 'like', '%' . $name . '%');
				}
			})
			->order('m.id', 'desc')
			->paginate($pageSize);
	}

	/**
	 * 获取所有的商户
	 *
	 * @return Merchants[]|bool|PDOStatement|string|Collection
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function getAllMerchant()
	{
		return Merchants::field('id, name')->where('status', 1)->select();
	}

	/**
	 * 获取商户详情
	 *
	 * @param $id
	 * @return Merchants|array|bool|PDOStatement|string|Model|null
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function getMerchant($id)
	{
		return Merchants::alias('m')
			->field('m.*,a.username')
			->join('admin a', 'a.id = m.admin_id', 'left')
			->where('m.id', $id)
			->find();
	}

	/**
	 * 添加商户
	 *
	 * @param $params
	 * @param $roomData
	 * @return void
	 */
	public static function addMerchant($params, $roomData)
	{
		$addData = [
			'token' => $params['m_token'],
			'secret' => $params['secret'],
			'name' => $params['name'],
			'rtp_id' => $params['rtp_id'],
			'cash_get_url' => $params['cash_get_url'],
			'cash_transferinout_url' => $params['cash_transferinout_url'],
			'mark' => $params['mark'],
			'style_name_switch' => $params['style_name_switch'],
			'style_bb_switch' => $params['style_bb_switch'],
			'style_ec_switch' => $params['style_ec_switch'],
			'style_oj_switch' => $params['style_oj_switch'],
			'type' => 0,
			'status' => $params['status'],
			'created_at' => date('Y-m-d H:i:s'),
			'platform_id' => $params['platform_id'],
			'free_spin_switch' => $params['free_spin_switch'],
			'auto_max' => $params['auto_max'],
			'm_path' => $params['m_path'],
			'static_domain' => $params['static_domain'],
		];
		$merchantId = Merchants::insertGetId($addData);
//		self::addRoomConfig($merchantId, $roomData);
		$salt = Random::alnum();
		$adminData = [
			'username' => $params['username'],
			'nickname' => $params['name'],
			'salt' => $salt,
			'password' => CommonService::getEncryptPassword($params['password'], $salt),
			'avatar' => '/assets/img/avatar.png',
			'platform_id' => $params['platform_id'],
			'merchant_id' => $merchantId,
		];
		$adminId = Admin::insertGetId($adminData);
		AuthGroupAccess::insert([
			'uid' => $adminId,
			'group_id' => 3,
		]);
		Merchants::where('id', $merchantId)->update(['admin_id' => $adminId]);
	}

	/**
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 * @throws Exception
	 */
	public static function editMerchant($adminUser, $params)
	{
		$id = $params['id'];
		$AuthGroupAccessStatus = 1;
		$thisAdminAccess = AuthGroupAccess::where('uid', $adminUser->id)->value('group_id');
		if ($thisAdminAccess == 2) {
			$AuthGroupAccessStatus = 2;
			$row = Merchants::where('id', $id)->where('platform_id', $adminUser->platform_id)->find();
		} else if ($thisAdminAccess == 3) {
			$AuthGroupAccessStatus = 2;
			$row = Merchants::where('id', $id)
				->where('platform_id', $adminUser->platform_id)
				->where('id', $adminUser->merchant_id)
				->find();
		} else {
			$row = Merchants::where('id', $id)->find();
		}
		$adminData = [];
		if (!empty($params['password'])) {
			$adminData['salt'] = Random::alnum();
			$adminData['password'] = CommonService::getEncryptPassword($params['password'], $adminData['salt']);
		}
		$update = [];
		if ($AuthGroupAccessStatus == 1) {
			// 判断账号是否存在
			if (!empty($row['admin_id'])) {
				$thisAdminWhere['id'] = ['neq', $row['admin_id']];
				$thisAdminWhere['username'] = ['eq', $params['username']];
				$thisAdmin = Admin::where($thisAdminWhere)->find();
			} else {
				$thisAdminWhere['username'] = ['eq', $params['username']];
				$thisAdmin = Admin::where('username', $params['username'])->find();
			}
			if ($thisAdmin) {
				throw new Exception(__('Username is exist'));
			}
			$update['name'] = $params['name'];
			$update['token'] = $params['m_token'];
			$update['secret'] = $params['secret'];
			$update['mark'] = $params['mark'];
			$update['status'] = $params['status'];
			$adminData['username'] = $params['username'];
			$adminData['nickname'] = $params['name'];
			$update['m_path'] = $params['m_path'];
			$update['static_domain'] = $params['static_domain'];
		}

		$update['free_spin_switch'] = $params['free_spin_switch'];
		$update['auto_max'] = $params['auto_max'];
		$update['rtp_id'] = $params['rtp_id'];
		$update['cash_get_url'] = $params['cash_get_url'];
		$update['cash_transferinout_url'] = $params['cash_transferinout_url'];
		$update['style_name_switch'] = $params['style_name_switch'];
		$update['style_bb_switch'] = $params['style_bb_switch'];
		$update['style_ec_switch'] = $params['style_ec_switch'];
		$update['style_oj_switch'] = $params['style_oj_switch'];
		$update['ip_array'] = $params['ip_array'] ?? null;
		$update['official_currency'] = $params['official_currency'];
		$update['updated_at'] = date('Y-m-d H:i:s');
		if (!empty($row['admin_id'])) {
			if (!empty($adminData)) {
				Admin::where('id', $row['admin_id'])->update($adminData);
			}
		} else {
			$adminData['avatar'] = '/assets/img/avatar.png';
			$adminData['platform_id'] = $row['platform_id'];
			$adminData['merchant_id'] = $row['id'];
			$adminId = Admin::insertGetId($adminData);
			$update['admin_id'] = $adminId;
			AuthGroupAccess::insert([
				'uid' => $adminId,
				'group_id' => 3,
			]);
		}
		Merchants::where('id', $row['id'])->update($update);
	}

	/**
	 * 删除商户
	 *
	 * @param $id
	 * @return void
	 */
	public static function deleteMerchant($id)
	{
		Merchants::where('id', $id)->delete();
	}

	/**
	 * 新增时获取房间数据
	 *
	 * @return array
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function getAddRoomData(): array
	{
		$roomData = Room::where('reward_status', 1)->field('id,name')->select();
		foreach ($roomData as &$vo) {
			$RoomGroupConfig = RoomGroupConfig::where('room_id', $vo['id'])->field('id,name')->order('id desc')->select();
			$vo['config'] = $RoomGroupConfig;
		}
		return $roomData;
	}

	/**
	 * 编辑时获取房间数据
	 *
	 * @param $adminUser
	 * @param $id
	 * @return array
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException|Exception
	 */
	public static function getEditRoomData($adminUser, $id): array
	{
		$thisAdminAccess = AuthGroupAccess::where('uid', $adminUser->id)->value('group_id');
		if ($thisAdminAccess == 2) {
			$row = Merchants::where('id', $id)->where('platform_id', $adminUser->platform_id)->find();
		} else if ($thisAdminAccess == 3) {
			$row = Merchants::where('id', $id)
				->where('platform_id', $adminUser->platform_id)
				->where('id', $adminUser->merchant_id)
				->find();
		} else {
			$row = Merchants::where('id', $id)->find();
		}
		if (!$row) {
			throw new Exception(__('No Results were found'));
		}
		$adminSelectInfo = Admin::where('id', $row['admin_id'])->find();
		if (!$adminSelectInfo) {
			$row['admin_id'] = '';
		}
		$roomData = Room::where('reward_status', 1)->field('id,name')->select();
		$row['username'] = '';
		if (!empty($row['admin_id'])) {
			$adminInfo = Admin::where('id', $row['admin_id'])->field('id,username')->find();
			$row['username'] = $adminInfo['username'] ?? '';
		}
		return $roomData;
	}

	/**
	 * 新增房间数据
	 *
	 * @param $id
	 * @param $data
	 * @return void
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function addRoomConfig($id, $data)
	{
		$newRoomArray = [];
		foreach ($data as $vo) {
			$thisArray = explode(':', $vo);
			$newRoomArray[] = [
				'room_id' => $thisArray[0],
				'group_id' => $thisArray[1],
			];
		}
		$addData = [];
		foreach ($newRoomArray as $vo) {
			if (!empty($vo['group_id'])) {
				$groupIds = RoomGroupConfig::where('room_id', $vo['room_id'])->where('id', $vo['group_id'])->value('group_ids');
			} else {
				$groupIds = RoomGroupConfig::where('room_id', $vo['room_id'])->value('group_ids');
			}
			$configData = BetAmountConfig::where('room_id', $vo['room_id'])->select();
			foreach ($configData as $vv) {
				$addData[] = [
					'room_id' => $vo['room_id'],
					'group_id' => $groupIds,
					'bet_amount' => $vv['bet_amount'],
					'merchant_id' => $id,
					'reward_id' => $vo['group_id'],
				];
			}
		}
		BetGroupConfig::insertAll($addData);
	}
}