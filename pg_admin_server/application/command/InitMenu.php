<?php

namespace app\command;

use app\admin\library\Auth;
use app\common\model\AuthRule;
use think\console\Input;
use think\console\Output;

class InitMenu extends \think\console\Command
{
	protected $menus = [
		['type' => 'file', 'pid' => 0, 'name' => 'game', 'title' => '游戏管理', 'ismenu' => 1,
			'child' => [
				['type' => 'file', 'name' => 'room/getRoomList', 'title' => '游戏列表', 'ismenu' => 1,
					'child' => [
						['type' => 'file', 'name' => 'room/addRoom', 'title' => '新增游戏', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/editRoom', 'title' => '编辑游戏', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/deleteRoom', 'title' => '删除游戏', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/deleteRooms', 'title' => '批量删除游戏', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/switchRoomStatus', 'title' => '切换游戏开关', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/switchRoomRewardStatus', 'title' => '切换游戏返奖开关', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/editRoomsType', 'title' => '批量编辑游戏状态', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/refreshSetting', 'title' => '刷新游戏设置', 'ismenu' => 0]]],
				['type' => 'file', 'name' => 'room/getTypeList', 'title' => '游戏分类', 'ismenu' => 1,
					'child' => [
						['type' => 'file', 'name' => 'room/getAllType', 'title' => '获取所有游戏分类', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/addType', 'title' => '新增游戏分类', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/editType', 'title' => '编辑游戏分类', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/deleteType', 'title' => '删除游戏分类', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/switchType', 'title' => '切换游戏分类开关', 'ismenu' => 0]
					]
				],
				[
					'type' => 'file',
					'name' => 'room/winroom',
					'title' => 'WinGo配置',
					'ismenu' => 1,
					'child' => [
						['type' => 'file', 'name' => 'room/addWinGoSet', 'title' => '新增WinGo设置', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/editWinGoSet', 'title' => '编辑WinGo设置', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/winGoSetSwitch', 'title' => 'WinGo设置开关', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/deleteWinGoSet', 'title' => '删除WinGo设置', 'ismenu' => 0],
					]
				],
				[
					'type' => 'file',
					'name' => 'room/winGoOperationLog',
					'title' => 'WinGo操作日志',
					'ismenu' => 1,
					'child' => [
					
					]
				],
				[
					'type' => 'file',
					'name' => 'room/wingoRealtime',
					'title' => '实时WinGo',
					'ismenu' => 1,
					'child' => [
						['type' => 'file', 'name' => 'room/roomName', 'title' => 'wingo房间名列表', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/wingoRoom', 'title' => 'wingo房间列表', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/latestData', 'title' => 'wingo房间总统计', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/details', 'title' => 'wingo房间明细', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/winningSubmit', 'title' => '预设开奖信息', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/wingoForecastLogs', 'title' => '预估记录', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/wingoLogs', 'title' => '单局wingo记录', 'ismenu' => 0],
						['type' => 'file', 'name' => 'room/updateBalance', 'title' => '更新余额', 'ismenu' => 0],
					]
				],
			]
		]
	];

	protected function configure()
	{
		$this->setName('init-menu')
			->setDescription('添加菜单');
	}

	public function importMenu(array $menus, $pid = 0)
	{
		foreach ($menus as $menu) {
			// 判断是否已存在
			$exists = AuthRule::where('name', $menu['name'])->find();

			if ($exists) {
				// 已存在则使用数据库中的 id
				$currentId = $exists['id'];
			} else {
				// 插入并获取 id
				$currentId = AuthRule::insertGetId([
					'pid'    => $pid,
					'name'   => strtolower($menu['name']),
					'title'  => $menu['title'],
					'type'   => $menu['type'],
					'ismenu' => $menu['ismenu'],
					'status' => 'normal'
				]);
			}

			// 递归处理子菜单
			if (isset($menu['child']) && is_array($menu['child'])) {
				$this->importMenu($menu['child'], $currentId);
			}
		}
	}

	protected function execute(Input $input, Output $output)
	{
		$this->importMenu($this->menus);
		(new Auth())->clearMenuCache();
		$output->writeln('初始化成功');
	}
}