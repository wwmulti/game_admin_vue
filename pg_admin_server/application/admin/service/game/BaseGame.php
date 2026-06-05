<?php

namespace app\admin\service\game;

use app\admin\model\Room;
use app\admin\service\IGame;
use app\job\CheckGameJob;
use app\job\GameDataJob;
use think\Cache;
use think\Queue;

/**
 * 游戏基类 - 提供通用实现
 */
abstract class BaseGame implements IGame
{

    /**
     * 队列数量
     */
    const QUEUE_NUMS = 5;

    protected $gameTypes;

    protected $gameType;

    /**
     * 统一域名
     *
     * @var string
     */
    protected $gameDomain = '';

    /**
     * 调用的游戏接口（多种可能）
     *
     * @var array
     */
    protected $gameSpins = ['Spin', 'spin', 'Play'];

    public function __construct()
    {
        $this->gameDomain = config('constant.domain');
        $this->gameTypes = config('constant.game_types');
    }

    /**
     * 登录验证
     */
    abstract public function verify($params);

    
    /**
     * 提交游戏数据
     */
    public function submitGameData($data)
    {
    }
    /**
     * 提交游戏检测
     */
    public function submitCheckGame($data)
    {
        $typeId = array_search($this->gameType, $this->gameTypes);
        if (!$typeId) {
            throw new \Exception('游戏类型不存在');
        }

        $url = sprintf('%s/api/web/game_url', $this->gameDomain);
        $games = Room::where('status', 1)->field('id,code')->where('type_id', $typeId)->select();
        foreach ($games as $game) {
            //构造加密请求参数(同一类型游戏只有game_code的不一样)
            $body = $data;
            $body['game_code'] = $game['code'];
            $md5 = md5(http_build_query($body));
            $body['sign'] = $md5;
            $body['ts'] = (int)$body['ts'];
            unset($body['key']);
            $list = ['game_code' => $game['id'], 'type' => $body['type'], 'type_id' => $typeId, 'url' => $url, 'body' => $body];
            Queue::push(CheckGameJob::class, $list, 'check_game_batch');

            //重置缓存
            Cache::store('redis')->handler()->del(sprintf('game_check:%s:', $body['type']));
        }
    }

    /**
     * 提交游戏数据入队列
     *
     * @param $logId 统计记录id
     * @param $url 游戏接口地址
     * @param $body 请求体
     * @param $rounds 轮次
     * @param $nums 每轮局数
     * @param $type 游戏类型
     * @return void
     */
    public function submitGameDataToQueue($logId, $url, $body, $rounds, $nums, $fix = 0)
    {
        $queueNums = self::QUEUE_NUMS;
        $total = $rounds;
        $runPerBatch = intval($total / $queueNums); //每批需要的轮次
        $remain = $total % $queueNums;

        for ($batch = 1; $batch <= $queueNums; $batch++) {
            //最后一批数据单独处理
            $end = 0;
            if ($batch == $queueNums) {
                $end = $runPerBatch + $remain;
            }

            $data = [
                'batch' => $batch,  //队列批次
                'per' => $runPerBatch, //每批次需要的轮次
                'nums' => $nums, //每轮次跑的数量
                'url' => $url,
                'body' => $body,
                'end' => $end,
                'log_id' => $logId,
                'type' => $this->gameType,
                'fix' => $fix
            ];

            Queue::push(GameDataJob::class, $data, 'game_data_batch_' . $batch);
        }
        return;
    }
    
}
