<?php

namespace app\common\services;

use app\admin\model\Rtp;
use PDOStatement;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Paginator;

class RtpService
{

	/**
	 * 获取rtp列表
	 *
	 * @param $params
	 * @return Paginator
	 * @throws DbException
	 */
	public static function getRtpList($params): Paginator
	{
		$pageSize = $params['pageSize'] ?? 10;
		$rtp = $params['rtp'] ?? null;
		$id = $params['id'] ?? null;
		return Rtp::where(function ($query) use ($id) {
				if ($id) {
					$query->where('id', $id);
				}
			})
			->where(function ($query) use ($rtp) {
				if ($rtp) {
					$query->where('rtp', 'like', '%' . $rtp . '%');
				}
			})
			->order('id', 'desc')
			->paginate($pageSize);
	}

	/**
	 * @return Rtp[]|bool|PDOStatement|string|Collection
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 * @throws DbException
	 */
	public static function getAllRtp()
	{
		return Rtp::field('id,rtp')->select();
	}

	/**
	 * 新增rtp
	 *
	 * @param $params
	 * @return void
	 */
	public static function addRtp($params)
	{
		$rtp = $params['rtp'];
		$rtpModel = new Rtp();
		$rtpModel->rtp = $rtp;
		$rtpModel->status = 1;
		$rtpModel->save();
	}

	/**
	 * 编辑rtp
	 *
	 * @param $params
	 * @return void
	 * @throws DataNotFoundException
	 * @throws DbException
	 * @throws ModelNotFoundException
	 */
	public static function editRtp($params)
	{
		$id = $params['id'];
		$rtp = $params['rtp'];
		$rtpModel = Rtp::where('id', $id)->find();
		if (!$rtpModel) {
			return;
		}
		$rtpModel->rtp = $rtp;
		$rtpModel->status = 1;
		$rtpModel->save();
	}

	/**
	 * 删除rtp
	 *
	 * @param $params
	 * @return void
	 */
	public static function deleteRtp($params)
	{
		$id = $params['id'];
		Rtp::where('id', $id)->delete();
	}
}