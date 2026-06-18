<?php

namespace app\admin\controller\test;

use app\admin\model\merchant\Merchants;
use app\admin\model\Room;
use app\admin\model\Rtp;
use app\common\controller\Backend;
use think\exception\DbException;
use think\response\Json;
use app\admin\model\BetAmountConfig;
use app\admin\service\GameFactory;
use app\admin\service\GameService;
use think\Db;
use think\Env;

class SpinStatistic extends Backend
{

    protected $noNeedRight = ['room_name', 'room_config', 'imitatePost', 'pollStatus', 'checkGames', 'getCheckGames', 'imitateInit'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SpinStatistic;

       
    }

    /**
     * 查看
     */
    public function index()
    {
        $rtp_list = \app\admin\model\Rtp::column('rtp','id');
        [$where, $sort, $order, $offset, $limit] = $this->buildparams(
            ['bet_amount' => 'range', 'bet_time' => 'range', 'bet_total' => 'range', 'win_total' => 'range', 'spe_times' => 'range', 'spe_total' => 'range', 'free_times' => 'range', 'free_win' => 'range', 'created_at' => 'times'], 
            ['type_id']
        );
        $sort = 'created_at';

        $formateValue = function ($fields) use ($where) {
            foreach ($fields as $field) {
                if(!empty($where[$field])){
                    $temp = $where[$field];
                    $temp[1][0] = $temp[1][0] * 1000;
                    $temp[1][1] = $temp[1][1] * 1000;
                    $where[$field] = $temp;
                }
            }
            return $where;
        };
        $where = $formateValue(['bet_amount','bet_time','bet_total','win_total','spe_total','free_win']);

        if (!empty($where['created_at'])) {
            $where['created_at'][0][1] = $where['created_at'][0][1] * 1000;
            $where['created_at'][1][1] = $where['created_at'][1][1] * 1000;
        }

        $rooms =  Room::where("type_id",$_GET["type_id"] ?? 2)->where('status', '1')->where('reward_status', 1)->field('name,id')->column( 'name',"id");
       


        $list = $this->model
            ->where($where)
            ->whereIn("room_id",array_keys($rooms))
            ->order($sort, $order)
            ->paginate($limit);

        foreach ($list as &$vo) {
            $vo['rtp'] = $rtp_list[$vo['rtp_id']] ?? '-';
            $vo['room_name'] = $rooms[$vo['room_id']] ?? '';
            $vo['bet_amount'] = FormatMoney($vo['bet_amount']);
            $vo['bet_total'] = FormatMoney($vo['bet_total']);
            $vo['win_total'] = FormatMoney($vo['win_total']);
            $vo['spe_total'] = FormatMoney($vo['spe_total']);
            $vo['free_win'] = FormatMoney($vo['free_win']);
            $vo['rebate_per'] = ($vo['win_total']*100 / $vo['bet_total']) . "%";
            $vo['win_per'] = ($vo['win_times']*100 / $vo['bet_time']) . "%";
            //创建时间
            $vo['created_at'] = bcdiv($vo['created_at'], 1000);

            $vo['special_trigger_probability'] = bcmul(bcdiv($vo['spe_times'],$vo['bet_time'],4),100,2). "%";
            $vo['free_trigger_probability'] =  bcmul(bcdiv($vo['free_times'],$vo['bet_time'],4),100,2). "%";
        }

        $result = [
            'total' => $list->total(), 
            'rows' => $list->items(),
            'room' => $rooms,
            'rtps' => $rtp_list,
        ];
        return $this->jsonSucc($result);
    }


    public function room_name2()
    {
        $type_id = $this->request->get('type_id');
        $type_id = (int) $type_id ;

        $RtpsData = Rtp::field('id,rtp as name')->select();
        $RoomData = Room::where("type_id",$type_id)->where('status', '1')->where('reward_status', 1)->field('name,id')->select();
        $array = ['rtps' => $RtpsData, 'room' => $RoomData];
        return $this->jsonSucc($array);
    }

    public function room_name()
    {
        $type_id = $this->request->get('type_id');
        $type_id = (int) $type_id ;
        
        $where = [];
        if($type_id>0){
            $where['type_id'] = $type_id;
        }
        $post = $this->request->post('name');
        if (!empty($post)) {
            $where['name'] = ['like', "{$post}%"];
        }
        $RoomData = Room::where('status', '1')->where('reward_status', 1)->order("id","asc")->where($where)->field('')->column('id,name');
        return $this->jsonSucc($RoomData);
    }

    public function roomConfig()
    {
        $room_id = $this->request->get('room_id');
        $RoomData = BetAmountConfig::where('room_id', $room_id)->field('bet_amount')->select();
        foreach ($RoomData as $key => &$vo) {
            $vo['id'] = $key;
            $vo['bet_amount'] = FormatMoney($vo['bet_amount']);
        }
        if(empty($RoomData)){
            $RoomData=[
                ["id"=>1,"bet_amount"=>FormatMoney(1000)]
            ];
        }
        return $this->jsonSucc($RoomData);
    }

    public function add()
    {
        $this->error('无权限');
    }

    public function edit($ids = NULL)
    {
        $this->error('无权限');
    }

    public function del($ids = NULL)
    {
        $this->error('无权限');
    }

    public function apiPost()
    {
        if ($this->request->isPost()) {
            $room_id = $this->request->post('room_id');
            $bet_amount = $this->request->post('bet_amount');
            $number = $this->request->post('number');
            $rtp_id = $this->request->post('rtp_id');
            $room_type = $this->request->post('room_type',0);//0:普通，2：必中
            if (empty($room_id)) {
               return $this->jsonError('请选择房间');
            }else if (empty($rtp_id)) {
                return $this->jsonError('请选择RTP');
            }  else if (empty($bet_amount)) {
                return $this->jsonError('请选择押注值');
            } else if (empty($number)) {
                return $this->jsonError('请输入押注次数');
            } else {

                $url = Env::get('app.game_request', '')."/api/home/spin_statistic";
                $post_data = [
                    'gid' => $room_id,
                    'bm' => $bet_amount * 1000,
                    'sn' => $number,
                    'mod' => $room_type,
                    'rtp_id' => $rtp_id,
                ];
                $this->curl($url,$post_data);
            }
            return $this->jsonSucc('请求成功');
        }
    }


    public function onceMore()
    {
        $id = $this->request->get('id');
        $room_data = $this->model->where('id', $id)->find();
        $url = Env::get('app.game_request', '') . "/api/home/spin_statistic";
        $post_data = [
            'gid' => $room_data['room_id'],
            'bm' => $room_data['bet_amount'],
            'sn' => $room_data['bet_time'],
            'mod' => $room_data['mod'],
            'rtp_id' => $room_data['rtp_id'],
        ];
        $this->curl($url, $post_data);
        return $this->jsonSucc();
    }

    /**
     * @param $url =》请求地址
     * @param $post_data =》请求数据
     * @param $header =》请求头
     * @param $type =》请求类型
     * 请求方法
     */
    public function curl($url, $post_data = '', $header = [], $type = 'post')
    {
        if ($post_data) {
            $type = 'post';
        }
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'post') {
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            if (is_array($post_data)) {
                $post_data = http_build_query($post_data);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        //https
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }

    /**
     * 模拟客户端界面
     *
     * @param GameService $gameService
     * @return string|Json
     */
    public function imitate(GameService $gameService)
    {
        //更新超时任务
        $logs = Db::table('game_run_stat')->where('status', 0)->select();
        if ($logs) {
            foreach ($logs as $log) {
                controller('app\backend\controller\Stat')->checkModifyStatus($log);
            }
        }

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $result = $gameService->getRunResultList($where, $sort, $order, $offset, $limit);        
        return $this->jsonSucc($result);
    }

    /**
     * 执行跑数据
     *
     * @param GameService $gameService
     * @return array|Json
     */
    public function imitatePost(GameService $gameService)
    {
        try {
            $err = $this->validate(
                $this->request->post(),
                [
                    'user_id|用户ID' => 'require|number',
                    'game_url|游戏地址' => 'require',
                    'nums|局数' => 'require|between:1,500',
                    'rounds|轮次' => 'require|between:1,10000',
                    'rtp|rtp' => 'number',
                    'type_id|游戏类型' => 'require|number',
                ]
            );
            if (true !== $err) {
                throw new \Exception($err);
            }
        } catch (\Exception $e) {
            return $this->jsonFail($e->getMessage());
        }

        if ($this->request->isPost()) {

            try {
                //检测游戏是否开启
                GameFactory::create($this->request->post('type_id'));

                $url = $this->request->post('game_url');
                $params = parse_url($url);
                $querys = [];
                parse_str($params['query'], $querys);

                //kess类型游戏
                if (input('post.type_id') == 5) {
                    $gameCode = $querys['gameId'];
                } elseif (input('post.type_id') == 11) { // PP 游戏
                    $code = $querys['symbol'] ?? '';
                    $game = Db::table('game_room')->where('code', $code)->find();
                    if (!$game) {
                        throw new \Exception('游戏不存在');
                    }
                    $gameCode = $game['id'];
                } else {
                    $matchs = [];
                    preg_match('/\/(\d+)\//', $params['path'], $matchs);
                    $gameCode = $matchs[1] ?? '';
                    if (!$gameCode) {
                        throw new \Exception('game_code获取失败');
                    }
                }                
                
                $querys['game_code'] = $gameCode;
                $querys['user_id'] = $this->request->post('user_id');
                $querys['nums'] = $this->request->post('nums');
                $querys['rounds'] = $this->request->post('rounds');
                $querys['rtp'] = $this->request->post('rtp');
                $querys['type_id'] = $this->request->post('type_id');
                $querys['save_data'] = $this->request->post('save_data/a', []);
                $querys['id'] = $this->request->post('id', 0, 'intval');
                [$tk, $path] = $gameService->verify($querys);

                $lastId = $gameService->runQueue($gameCode, $tk, $path, $querys);

                return $this->jsonSucc(['id' => $lastId]);
            } catch (\Exception $e) {
                return $this->jsonFail($e->getMessage());
            }
        }
    }

    /**
     * 查看结果接口
     *
     * @param GameService $gameService
     * @return void
     */
    public function getResult(GameService $gameService)
    {
        $id = $this->request->get('id', 0, 'intval');
        try {
            $data = $gameService->getRunResult($id);
            return $this->jsonSucc($data);
        } catch (\Exception $e) {
            return $this->jsonFail($e->getMessage());
        }   
    }

    /**
     * 轮询查看状态接口
     *
     * @return void
     */
    public function pollStatus()
    {
        $id = $this->request->get('id', 0, 'intval');
        $status = Db::table('game_run_stat')->where('id', $id)->value('status');
        return $this->jsonSucc(['status' => $status]);
    }

    /**
     * 游戏接口检测
     */
    public function checkGames(GameService $gameService)
    {
        $params = $this->request->post();
        try {
            $gameService->checkGamesQueue($params);
            return $this->jsonSucc();
        } catch (\Exception $e) {
            return $this->jsonFail($e->getMessage());
        }
    }

    /**
     * 获取游戏接口检测
     */
    public function getCheckGames(GameService $gameService)
    {
        try {
            $data = $gameService->getGameCheckResult(input('get.type', 'pg', 'trim'));
            return $this->jsonSucc($data);
        } catch (\Exception $e) {
            return $this->jsonFail($e->getMessage());
        }
    }

    /**
     * 模拟客户端初始化数据
     */
    public function imitateInit()
    {
        $rooms = Room::where('status', '1')->column('name,type_id,code', 'id');
        $gameTypeList = [];
        foreach ($rooms as $room) {
            $temp = ['code' => $room['code'], 'name' => $room['name']];
            $gameTypeList[$room['type_id']][] = $temp;
        }
        $result = [
            'game_types_list' => $gameTypeList,
            'game_types' => config('constant.game_types'),
            'languages' => config('constant.languages'),
            'page_domain' => config('constant.page_domain'),
        ];
        return $this->jsonSucc($result);
    }
}
