<?php

namespace app\admin\controller\analys;

use app\common\controller\Backend;
use app\admin\model\merchant\Merchants;
use app\common\model\database1\Room as RoomModel;
use app\admin\model\Rtp;
use app\job\GameDataAnalysJob;
use think\Queue;

class Game extends Backend
{

    protected $model = null;
    protected $noNeedRight = ['merchant_name', 'rooms'];

    public $roomIds = [1980, 68, 126, 98, 1695365, 1879752, 1543462, 2002004];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\AnalysTasks;
    }

    public function index()
    {
        return $this->gameAnalys();
    }

    public function merchant_name($array = false)
    {
        $merchant_data = Merchants::column('name', 'id');
        if ($array) {
            return $merchant_data;
        }
        return json($merchant_data);
    }

    public function rooms($array = false)
    {
        $game_types = config('constant.game_types');
        $room_data = RoomModel::where('status', 1)->where('id', 'in', $this->roomIds)->column('id,type_id,name');
        $result = [];
        foreach ($room_data as $key => $value) {
            $result[$key] = sprintf('%s-%s', $game_types[$value['type_id']] ?? $value['type_id'], $value['name']);
        }
        if ($array) {
            return $result;
        }
        return json($result);
    }

    public function rtps($array = false)
    {
        $rtp_data = Rtp::column('rtp', 'id');
        if ($array) {
            return $rtp_data;
        }
        return json($rtp_data);
    }

    public function gameAnalys()
    {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $params = $this->request->get();
        $stat = $params['stat'] ?? 0;
        if ($stat == 1) {
            if (!isset($params['game'])) {
                return $this->jsonFail('请选择游戏');
            }

            if (!isset($params['time'])) {
                return $this->jsonFail('请选择时间范围');
            }

            $data = [];
            list($startDate, $endDate) = $params['time'];
            $data['start_time'] = strtotime($startDate);
            $data['end_time'] = strtotime($endDate . ' 23:59:59');
            $data['merchant_id'] = $params['merchant'] ?? 0;

            $gameId = $params['game'] ?? 0;
            $game = RoomModel::where('id', $gameId)->field('name,history_table_name')->find();
            if (!$game) {
                return $this->jsonFail('游戏不存在');
            }
            if (empty($game['history_table_name'])) {
                return $this->jsonFail('游戏历史记录表不存在');
            }
            $data['history_table_name'] = $game['history_table_name'];
            $data['type'] = 0;

            $save = [];
            $save['type'] = 0;
            $save['status'] = 1;
            $save['condition'] = json_encode($params);
            $save['c_time'] = time();
            $lastId = $this->model->insertGetId($save);
            if (!$lastId) {
                return $this->jsonFail('游戏分析任务添加失败');
            }
            $data['id'] = $lastId;

            // 进入队列执行
            Queue::push(GameDataAnalysJob::class, $data, 'game_data_analys');
        }

        $list = $this->model
            ->where('type', 0) // 游戏分析
            // ->where($where)
            ->order('id', 'desc')
            ->paginate($limit);

        $MerchantArray = $this->merchant_name(true);
        $rooms = $this->rooms(true);
        $result = [];
        foreach ($list as $row) {
            $condition = json_decode($row->condition, true) ?: [];
            $result[] = [
                'id' => $row->id,
                'c_time' => date('Y-m-d H:i:s', $row->c_time),
                'task_time' => sprintf('%s ~ %s', $condition['time'][0], $condition['time'][1]),
                'merchant_name' => $MerchantArray[$condition['merchant'] ?? 0] ?? '-',
                'game_name' => $rooms[$condition['game'] ?? 0] ?? '-',
                'status' => $row->status,
                'remark' => $row->remark ?: '-',
            ];
        }

        return $this->jsonSucc([
            'total' => $list->total(),
            'rows' => $result,
            'merchant_name' => $MerchantArray,
            'rooms' => $rooms,
        ]);
    }

    public function gameAnalysDetail($id)
    {
        $row = $this->model->find($id);
        if (!$row) {
            if ($this->request->isAjax()) {
                return $this->jsonFail('记录不存在');
            }
            return $this->jsonFail('记录不存在');
        }
        
        $condition = json_decode($row->condition, true) ?: [];
        $result = json_decode($row->result, true) ?: [];
        
        $merchant_name = Merchants::where('id', $condition['merchant'] ?? 0)->value('name') ?: '-';
        $game_types = config('constant.game_types');
        $room_info = RoomModel::where('id', $condition['game'] ?? 0)->find();
        $game_name = $room_info ? sprintf('%s(%s)', $room_info['name'], $game_types[$room_info['type_id']] ?? $room_info['type_id']) : '-';
        $rtpData = $this->rtps(true);
        foreach ($result as &$val) {
            if (isset($rtpData[$val['rtp_id']])) {
                $val['game_rtp'] = sprintf('%s-%s', $game_name, $rtpData[$val['rtp_id']]);
            } else {
                $val['game_rtp'] = sprintf('%s-rtp_id%s', $game_name, $val['rtp_id']);
            }
        }

        $data = [
            'id' => $row->id,
            'time' => sprintf('%s ~ %s', $condition['time'][0], $condition['time'][1]),
            'merchant' => $merchant_name,
            'game' => $game_name,
            'result' => $result,
        ];
        return $this->jsonSucc($data);
    }

    // 轮询检测状态
    public function checkStatus($type = 0)
    {
        $count = $this->model
            ->where('type', $type)
            ->where('status', 1)
            ->count();
        return $this->jsonSucc(['count' => $count]);
    }

}
