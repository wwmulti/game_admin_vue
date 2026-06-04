<?php

namespace app\common\services;

use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\merchant\Platform;
use PDOStatement;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class PlatformService
{
	/**
	 * 获取平台列表
	 *
	 * @param $adminUser
	 * @param $params
	 * @return array
	 * @throws DbException
	 */
	public static function getPlatformList($adminUser, $params): array
	{
		$pageSize = $params['pageSize'] ?? 10;
		$name = $params['name'] ?? null;
		$status = $params['status'] ?? null;
		$createStart = $params['createStartTime'] ?? null;
		$createEnd = $params['createEndTime'] ?? null;
		$updateStart = $params['updateStartTime'] ?? null;
		$updateEnd = $params['updateEndTime'] ?? null;

		$list = Platform::alias('p')
			->field('p.*,a.username')
			->join('admin a', 'a.id = p.admin_id', 'left')
			->where(function ($query) use ($adminUser) {
				if ($adminUser['group_id'] == 2 || $adminUser['group_id'] == 3) {
					$query->where('a.id', $adminUser['platform_id']);
				}
			})
			->where(function ($query) use ($name) {
				if ($name) {
					$query->where('p.name', 'like', '%' . $name . '%');
				}
			})->where(function ($query) use ($status) {
				if (!is_null($status)) {
					$query->where('p.status', $status);
				}
			})->where(function ($query) use ($createStart, $createEnd) {
				if ($createStart && $createEnd) {
					$query->whereBetween('p.createtime', [strtotime($createStart), strtotime($createEnd)]);
				}
			})->where(function ($query) use ($updateStart, $updateEnd) {
				if ($updateStart && $updateEnd) {
					$query->whereBetween('p.updatetime', [strtotime($updateStart), strtotime($updateEnd)]);
				}
			})->order('p.id', 'desc')
			->paginate($pageSize)
			->toArray();
		foreach ($list['data'] as &$item) {
			$item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
			$item['updatetime'] = date('Y-m-d H:i:s', $item['updatetime']);
		}

		return $list;
	}

	/**
	 * 获取所有平台
	 *
	 * @return Platform[]|bool|PDOStatement|string|Collection
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function getAllPlatform()
	{
		return Platform::field('id,name')->where('status', 1)->select();
	}

	/**
	 * 添加平台
	 *
	 * @param $params
	 * @return void
	 * @throws DbException
	 * @throws Exception
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 */
	public static function addPlatform($params)
	{
		$name = $params['name'] ?? null;
		$username = $params['username'] ?? null;
		$password = $params['password'] ?? null;
		$status = $params['status'] ?? null;
		$searchAdmin = Admin::where('username', $username)->find();
		if ($searchAdmin) {
			throw new Exception(__('Admin is not exist'));
		}
		$admin = new Admin();
		$admin->username = $username;
		$admin->nickname = $name;
		$admin->salt = CommonService::createSalt();
		$admin->password = CommonService::getEncryptPassword($password, $admin->salt);
		$admin->status = $status == 1 ? 'normal' : 'hidden';
		$admin->avatar = '/assets/img/avatar.png';
		$admin->save();
		$platform = new Platform();
		$platform->name = $name;
		$platform->status = $status;
		$platform->admin_id = $admin->id;
		$platform->save();
		$admin->platform_id = $platform->id;
		$admin->save();
		AuthGroupAccess::insert([
			'uid' => $admin->id,
			'group_id' => 2,
		]);
	}

	/**
	 * 编辑平台
	 *
	 * @param $params
	 * @return void
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws Exception
	 * @throws ModelNotFoundException
	 */
	public static function editPlatform($params)
	{
		$id = $params['id'] ?? null;
		$username = $params['username'] ?? null;
		$password = $params['password'] ?? null;
		$name = $params['name'] ?? null;
		$status = $params['status'] ?? null;
		$platformUser = Platform::get($id);
		if (!$platformUser) {
			throw new Exception(__('Platform is not exist'));
		}
		$admin = Admin::get($platformUser->admin_id);
		if (!$admin) {
			throw new Exception(__('Admin is not exist'));
		}
		if ($username) {
			$searchAdmin = Admin::where('username', $username)->find();
			if ($searchAdmin && $searchAdmin->id != $admin->id) {
				throw new Exception(__('Username is exist'));
			} else {
				$admin->username = $username;
			}
		}
		if ($password) {
			$admin->password = CommonService::getEncryptPassword($password, $admin->salt);
		}
		if ($name) {
			$platformUser->name = $name;
		}
		if ($status === 0) {
			$platformUser->status = 0;
			$admin->status = 'hidden';
		} else {
			$platformUser->status = 1;
			$admin->status = 'normal';
		}
		$platformUser->save();
		$admin->save();
	}

	/**
	 * 删除平台
	 *
	 * @param $id
	 * @return void
	 */
	public static function deletePlatform($id)
	{
		Platform::where('id', $id)->delete();
	}
}