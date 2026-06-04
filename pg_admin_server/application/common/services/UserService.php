<?php

namespace app\common\services;

use app\admin\model\Room;
use app\admin\model\User;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class UserService
{
	/**
	 * 获取用户列表
	 *
	 * @param $params
	 * @return array
	 * @throws DbException
	 */
	public static function getUserList($params): array
	{
		$id = $params['id'] ?? null;
		$platformId = $params['platform_id'] ?? null;
		$merchantId = $params['merchant_id'] ?? null;
		$remoteUid = $params['remote_uid'] ?? null;
		$rtp = $params['rtp'] ?? null;
		$moneyStart = $params['moneyStart'] ?? null;
		$moneyEnd = $params['moneyEnd'] ?? null;
		$name = $params['name'] ?? null;
		$createStart = $params['createStartTime'] ?? null;
		$createEnd = $params['createEndTime'] ?? null;
		$pageSize = $params['pageSize'] ?? 10;
		$list = User::alias('u')
			->join('platform p', 'p.id = u.platform_id', 'left')
			->join('merchants m', 'm.id = u.merchant_id', 'left')
			->join('rtp r', 'r.id = u.rtp_id', 'left')
			->field('u.created_at, u.id, u.name, u.money, u.remote_uid, u.platform_id, u.merchant_id, u.rtp_id, p.name as platform_name, m.name as merchant_name, r.rtp as rtp')
			->where('r.status', 1)
			->where('m.status', 1)
			->whereNull('u.deleted_at')
			->where(function ($query) use ($id) {
				if ($id) {
					$query->where('u.id', $id);
				}
			})->where(function ($query) use ($platformId) {
				if ($platformId) {
					$query->where('u.platform_id', $platformId);
				}
			})->where(function ($query) use ($merchantId) {
				if ($merchantId) {
					$query->where('u.merchant_id', $merchantId);
				}
			})->where(function ($query) use ($remoteUid) {
				if ($remoteUid) {
					$query->where('u.remote_uid', 'like', '%' . $remoteUid . '%');
				}
			})->where(function ($query) use ($rtp) {
				if ($rtp) {
					$query->where('r.rtp', $rtp);
				}
			})->where(function ($query) use ($moneyStart, $moneyEnd) {
				if ($moneyStart && $moneyEnd) {
					$query->whereBetween('u.money', [$moneyStart, $moneyEnd]);
				}
			})->where(function ($query) use ($name) {
				if ($name) {
					$query->where('u.name', 'like', '%' . $name . '%');
				}
			})->where(function ($query) use ($createStart, $createEnd) {
				if ($createStart && $createEnd) {
					$query->whereBetween('u.created_at', [$createStart, $createEnd]);
				}
			})->order('u.id', 'desc')
			->paginate($pageSize)
			->toArray();
		foreach ($list['data'] as &$row) {
			$row['money'] = CommonService::formatMoney($row['money']);
		}

		return $list;
	}


	/**
	 * 获取用户余额
	 *
	 * @param $id
	 * @return string|null
	 */
	public static function getUserMoney($id): ?string
	{
		$result = User::where('id', $id)->value('money');
		return CommonService::formatMoney($result);
	}

	/**
	 * 获取游戏历史记录
	 *
	 * @param $params
	 * @return array
	 * @throws DbException
	 * @throws Exception
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 */
	public static function getUserGameHistoryList($params): array
	{
		$id = $params['id'] ?? null;
		$roomId = $params['room_id'];
		$userId = $params['user_id'];
		$createStart = $params['createStartTime'] ?? null;
		$createEnd = $params['createEndTime'] ?? null;
		$pageSize = $params['pageSize'] ?? 10;
		if (!$roomId) {
			throw new Exception(__('Please select game'));
		}
		$room = Room::where('id', $roomId)->find();
		$table = $room->history_table_name;
		$name = $room->name;
		$list = Db::table($table)->where(function ($query) use ($userId) {
			if ($userId) {
				$query->where('user_id', $userId);
			}
		})->where(function ($query) use ($createStart, $createEnd) {
			if ($createStart && $createEnd) {
				$query->whereBetween('created_at', [strtotime($createStart) * 1000, strtotime($createEnd) * 1000]);
			}
		})->order('id', 'desc')
			->paginate($pageSize)
			->toArray();
		foreach ($list['data'] as &$row) {
			$row['room_name'] = $name;
			$row['created_at'] = date('Y-m-d H:i:s', bcdiv($row['created_at'],1000));
		}
		return $list;
	}
}