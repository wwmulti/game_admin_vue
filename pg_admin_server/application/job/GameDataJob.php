<?php

namespace app\job;

use app\admin\service\GameFactory;
use app\admin\service\game\PgGame;
use think\Cache;
use think\Console;
use think\Db;
use app\common\library\BinaryStreamWriter;
use app\common\library\BinaryStreamReader;

class GameDataJob
{
    protected $dataStore = [];

    protected $susNum = 0; //成功执行的次数

    protected $errNum = 0; //请求失败的次数

    protected $errLimit = 15; //请求失败的次数上限就认为是失败

    protected $bl = 0; //wg专用，记录每局后的余额

    protected $limitTime = 900; //任务超时时间，超过15分钟认为是超时

    protected $cacheTime = 1200; //缓存数据时间

    protected $result = []; // 用于记录pp下一轮的开始（按psid不同记录为不同的局）

    public function fire($job, $data)
    {
        set_time_limit(0);
        $batch = $data['batch'];
        $per = $data['per'];
        $end = $data['end'];
        $logId = $data['log_id'];
        $fix = $data['fix'] ?? 0;
        $log = Db::table('game_run_stat')->where('id', $logId)->find();
        try {
            $start = ($batch - 1) * $per + 1;
            if ($end) {
                $end = $start + $end - 1;
            } else {
                $end = $batch * $per;
            }

            $redis = Cache::store('redis');

            $url = $data['url'];
            $body = $data['body'];
            $nums = $data['nums'];
            $type = strtolower($data['type']); //游戏类型

            //wg和cp和pp游戏需要特殊处理，记录每局后的余额
            if (in_array($type, [GameFactory::GAME_TYPE_WG_STR, GameFactory::GAME_TYPE_CP_STR, GameFactory::GAME_TYPE_PP_STR, GameFactory::GAME_TYPE_UU_STR])) {   
                $this->bl = $body['bl'];
                unset($body['bl']);
            }

            //根据不同游戏类型分配不同处理方法
            $taskFuncName = sprintf('%sTask', $type);

            $startTime = time();
            for ($i = $start; $i <= $end; $i++) {
                if (time() - $startTime > $this->limitTime) {
                    throw new \Exception(sprintf('任务超时，超过%s秒', $this->limitTime), 10001);
                }

                call_user_func([$this, $taskFuncName], $url, $body, $i, $redis, $logId, $nums);
                // usleep(10000);
            }

            Db::table('game_run_stat')->where('id', $logId)->update(['queue_' . $batch => 1]);
        } catch (\Throwable $t) {
            $code = $t->getCode();
            $status = 2; // 失败
            if ($code === 10001) {
                $status = 3; // 超时
            }
            $errMsg = sprintf('%s;%s', $log['err_msg'] ?? '', $t->getMessage());
            Db::table('game_run_stat')->where('id', $logId)->update(['queue_' . $batch => 1, 'status' => $status, 'err_msg' => $errMsg]);
            save_log('game_data_job_error_out', json_encode([
                'log_id' => $logId,
                'batch' => $batch,
                'error' => $t->getMessage(),
            ]));
        } finally {
            $this->doDataStore($fix);
            // 任务处理完成后，删除任务
            $job->delete();
        }
    }

    public function failed($data)
    {
        var_dump('任务达到最大重试次数后，失败处理');
    }

    /**
     * pg任务
     */
    protected function pgTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);

        $gameCode = $body['game_code'] ?? '';
        $uid = $body['uid'] ?? '';
        $tk = $body['tk'] ?? '';
        $currency = $body['currency'] ?? '';
        unset($body['tk'], $body['game_code'], $body['currency'], $body['uid']);

        if (in_array($gameCode, PgGame::GAME_CODE_EX)) {
            $header = [
                'Content-Type: application/x-www-form-urlencoded',
                'gwcurrency: ' . $currency,
                'gwservertype: ' . $gameCode,
                'gwuid: ' . $uid,
                'gwuri: /index',
                'token: ' . $tk
            ];
            $info = http_build_query([
                'command' => json_encode($body)
            ]);
            $headers = [CURLOPT_HTTPHEADER => $header];
        } else {
            $info = http_build_query($body);
            $headers = [];
        }

        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) break;

                if ($fails >= $this->errLimit) {
                    break;
                }

                $result = wget($url, $info, $headers);
                // var_dump($result);
                if (empty($result['dt']) || !empty($result['err'])) {
                    $fails++;
                    $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                    continue;
                }

                $data = $result['dt']['si'] ?? [];
                if (empty($data) || ($data['nst'] ?? 0) != 1) continue; //只记录nst=1的数据（新的一局）
                // echo sprintf('aw: %s, tbb: %s, rate: %s' . PHP_EOL, $data['aw'], $data['tbb'], $data['aw'] / $data['tbb']);

                // 重置连续失败次数
                $fails = 0;

                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $data['aw']);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $data['tbb']);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;
            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    /**
     * wg任务
     */
    protected function wgTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);
        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) {
                    return;
                }

                if ($fails >= $this->errLimit) {
                    break;
                }

                $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
                // var_dump(json_encode($result));
                if (empty($result['dt']) || !empty($result['err'])) {
                    // if ($no >= $nums) return;
                    $fails++;
                    $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                    continue;
                }

                $data = $result['dt'];
                if (empty($data['bl'])) continue;
                
                $aw = $data['bl'] + $body['bet'] - $this->bl;
                $aw = $aw > 0 ? $aw : 0;
                $tbb = $body['bet'];
                $this->bl = $data['bl'];

                // 重置连续失败次数
                $fails = 0;
                
                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $tbb);
                // echo sprintf('round: %s, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $i, $bl, $data['bl'], $tbb, $aw);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;

                //免费局(计为同一局)
                if (isset($data['free']) && $data['free'] === true) {
                    $body1 = $body;
                    while (1) {
                        $body1['bet'] = 0;
                        $body1['mult'] = 0;
                        $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
                        if (empty($result['dt']) || !empty($result['err'])) {
                            break;
                        }

                        //免费局结束
                        if (!empty($result['dt']['bl'])) {
                            $aw = $result['dt']['bl'] - $this->bl;
                            $this->bl = $result['dt']['bl'];
                            $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                            // echo sprintf('--free, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $bl, $result['dt']['bl'], 0, $aw);
                            break;
                        }
                    }
                }

            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    /**
     * cp任务
     */
    protected function cpTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);

        $gameCode = $body['gameCode'] ?? '';
        unset($body['gameCode']);

        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) {
                    return;
                }

                if ($fails >= $this->errLimit) {
                    break;
                }

                $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
                // var_dump(json_encode($result));
                if (empty($result['dt']) || !empty($result['err'])) {
                    // if ($no >= $nums) return;
                    $fails++;
                    $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                    continue;
                }

                $data = $result['dt'];
                if (empty($data['bl'])) continue;

                $aw = $data['bl'] + $body['bet'] - $this->bl;
                $aw = $aw > 0 ? $aw : 0;
                $tbb = $body['bet'];
                $this->bl = $data['bl'];

                // 重置连续失败次数
                $fails = 0;
                
                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $tbb);
                // echo sprintf('round: %s, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $i, $bl, $data['bl'], $tbb, $aw);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;

                //免费局(计为同一局)-熊猫、幸运猫、魔法猫免费局客户端无再请求
                if (isset($data['free']) && $data['free'] === true && !in_array($gameCode, [2001001, 2001002, 2001006])) {
                    $body1 = $body;
                    while (1) {
                        $body1['bet'] = 0;
                        $body1['mult'] = 0;
                        $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type' => 'multipart/form-data']]);
                        if (empty($result['dt']) || !empty($result['err'])) {
                            break;
                        }

                        //免费局结束
                        if (!empty($result['dt']['bl'])) {
                            $aw = $result['dt']['bl'] - $this->bl;
                            $this->bl = $result['dt']['bl'];
                            $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                            // echo sprintf('--free, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $bl, $result['dt']['bl'], 0, $aw);
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    /**
     * kess任务
     */
    protected function kessTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);

        $accessToken = $body['accessToken'] ?? '';
        $channel = $body['channel'] ?? '';
        $gameCode = $body['gameCode'] ?? '';
        $bets = $body['bets'] ?? 0;
        unset($body['accessToken'], $body['channel'], $body['gameCode'], $body['bets']);

        //取结果
        if ($gameCode == 3000004) {
            $calcuFunc = function ($val) {
                return ($val['awardNum'] ?? 0) * ($val['betAmount'] ?? 0);
            };
        } elseif ($gameCode == 3000014) {
            $calcuFunc = function ($val) {
                return $val['awardNum'] ?? 0;
            };
        } else {
            $calcuFunc = function ($val) {
                return $val['totalAmount'] ?? 0;
            };
        }

        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) return;

                if ($fails >= $this->errLimit) {
                    break;
                }
                
                $result = wget($url, json_encode($body), [CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . $accessToken, 'Channelid: ' . $channel]]);
                // var_dump($result);
                if (empty($result['data']) || (isset($result['success']) && !$result['success'])) {
                    $fails++;
                    $msg = '数据异常';
                    continue;
                }

                $aw = $calcuFunc($result['data']);
                $tbb = $bets;

                // 重置连续失败次数
                $fails = 0;
                
                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $tbb);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;
            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    /**
     * pp任务
     * 游戏下注
     * 第一次请求为action=doSpin 会获取到psid (只有action=doSpin会有参数psid)
     * 得到下注金额为 参数c * 参数l （psid 相同，只扣第一次的钱），中奖金额为 w，相同psid的w累加
     * 如果结果 参数na=s 继续请求action=doSpin ,直到psid不同（开始下一局）
     * 如果结果 参数na=c ，一定要请求一次 action = doCollect 然后这局结束，下一局重新开始（不扣金额，不累计w，没有参数psid，psid不变),接着处理action=doSpin,如此循环
     * 如果结果 擦拭na=b 一定要请求一次 action = doBonus 不做其他处理（不扣金额，不累计w，没有参数psid，psid不变),接着处理action=doSpin,如此循环
     */
    protected function ppTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);

        // 相同的psid同一局
        $currentPsid = "";
        $roundAw = 0;
        $roundTbb = floatval($body['c'] ?? 0) * floatval($body['l'] ?? 0);

        $bodyPost = $body;
        $gameCode = $bodyPost['gameCode'] ?? '';
        unset($bodyPost['gameCode']);

        // 初始化动作
        $bodyPost['action'] = 'doSpin';

        // === 1. 接力逻辑：检查是否有上一轮跑多了剩下的数据 ===
        if (!empty($this->result)) {
            $res = $this->result;
            $currentPsid = $res['psid'] ?? "";
            $roundAw = floatval($res['w'] ?? 0);

            // 根据暂存的 na 立即锁定下一个动作
            $this->syncActionByNa($bodyPost, $res['na'] ?? '', $body);
            $this->result = [];
        }

        foreach (range(1, $nums + $ext) as $no) {
            try {
                // 只有当：1. 局数够了 2. 且当前不是必须处理的后续动作（即 action 是 doSpin）
                if ($flag >= $nums && $bodyPost['action'] == 'doSpin') {
                    break;
                }

                if ($fails >= $this->errLimit) {
                    break;
                }

                $bodyPost['index'] = rand(1, 100000);
                $bodyPost['counter'] = rand(1, 100000);

                $ret = wget($url, http_build_query($bodyPost));
                // var_dump($ret);
                $lastAction = $bodyPost['action']; // 记录当前动作

                if (!$ret || !is_string($ret)) {
                    $fails++;
                    $msg = '数据异常';
                    continue;
                }

                $res = [];
                parse_str($ret, $res);
                if (!isset($res['na'])) {
                    $fails++;
                    $msg = '数据异常,na为空';
                    continue;
                }
               
                $na = $res['na'];
                $w = floatval($res['w'] ?? 0);
                $newPsid = $res['psid'] ?? "";

                // echo sprintf("i->%s psid->%s na->%s w->%s tbb->%.2f\n", $i, $newPsid, $na, $w, $roundTbb);
                
                // 只要这次请求发完了，就根据返回的 na 锁定下一次的动作
                $this->syncActionByNa($bodyPost, $na, $body);

                // 情况 A：刚才执行的是补完动作 (doCollect 或 doBonus)
                if ($lastAction !== 'doSpin') {
                    $this->result['na'] = ['s'];
                    $fails = 0;
                    continue;
                }

                // 情况 B：刚才执行的是正常的 doSpin
                // 只有在这里才处理 PSID 结算和 flag 计数
                if ($currentPsid !== "" && $newPsid !== "" && $newPsid !== $currentPsid) {
                    // 结算上一局
                    $redis->handler()->hIncrByFloat($cacheKey, 'aw', $roundAw);
                    $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $roundTbb);
                    $flag++;

                    // 判定局数是否已满
                    if ($flag >= $nums) {
                        // 满足局数，把当前这局的新数据存入 result 作为下一轮的起始
                        $res['c'] = $body['c'];
                        $res['l'] = $body['l'];
                        $this->result = $res;

                        // 如果需要收尾，continue 去跑最后一圈请求；否则直接 break
                        if ($bodyPost['action'] !== 'doSpin') {
                            $fails = 0;
                            continue;
                        }
                        break;
                    }

                    // 还没够，重置数据开始新局统计
                    $currentPsid = $newPsid;
                    $roundAw = $w;
                } elseif ($currentPsid === "" && $newPsid !== "") {
                    // 首次初始化
                    $currentPsid = $newPsid;
                    $roundAw = $w;
                } else {
                    // PSID 相同（同一局），累加中奖
                    $roundAw += $w;
                }

                $fails = 0;
                $redis->handler()->expire($cacheKey, $this->cacheTime);

            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    protected function syncActionByNa(&$bodyPost, $na, $originalBody)
    {
        if ($na == 'b') {
            $bodyPost['action'] = 'doBonus';
            $bodyPost['ind'] = 0;
            unset($bodyPost['c'], $bodyPost['l'], $bodyPost['sInfo']);
        } elseif ($na == 'c') {
            $bodyPost['action'] = 'doCollect';
            unset($bodyPost['c'], $bodyPost['l'], $bodyPost['sInfo'], $bodyPost['ind']);
        } else {
            // 只要不是 b 或 c，一律重置为 doSpin
            $bodyPost['action'] = 'doSpin';
            $bodyPost['c'] = $originalBody['c'];
            $bodyPost['l'] = $originalBody['l'];
            $bodyPost['sInfo'] = 'n';
            unset($bodyPost['ind']);
        }
        // echo sprintf("NA返回:%s -> 锁定下次执行动作:%s\n", $na, $bodyPost['action']);
    }

    /**
     * uu任务
     */
    protected function uuTask($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);

        $binaryWriter = new BinaryStreamWriter();
        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) {
                    return;
                }

                if ($fails >= $this->errLimit) {
                    break;
                }

                $postBody = $this->writeBinaryData($binaryWriter, $body);
                $result = wget($url, $postBody, [CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream']]);
                // var_dump($result);

                if (is_array($result) && (empty($result['dt']) || !empty($result['err']))) {
                    // if ($no >= $nums) return;
                    $fails++;
                    $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                    continue;
                }

                $data = $this->readBinaryData($result, $body);
                // var_dump($data);

                if (empty($data['bl'])) continue;

                $aw = $data['bl'] + $body['bet'] - $this->bl;
                if ($body['game_code'] == 2002005) {
                    $aw = $aw / 1000;
                }

                $aw = $aw > 0 ? $aw : 0;
                $tbb = $body['bet'];
                $bl = $this->bl;
                $this->bl = $data['bl'];

                // 重置连续失败次数
                $fails = 0;

                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $tbb);
                // echo sprintf('round: %s, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $i, $bl, $data['bl'], $tbb, $aw);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;

                //免费局(计为同一局)
                if (isset($data['free']) && $data['free'] === true) {
                    /* var_dump('free----------------------------start');
                    var_dump($data); */
                    $body1 = $body;
                    $body1['bet'] = 0;
                    $body1['mult'] = 0;
                    while (1) {
                        $postBody = $this->writeBinaryData($binaryWriter, $body1);
                        $result = wget($url, $postBody, [CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream']]);
                        if (is_array($result) && (empty($result['dt']) || !empty($result['err']))) {
                            $fails++;
                            $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                            break;
                        }

                        $tempData = $this->readBinaryData($result, $body1);
                        // var_dump($tempData);

                        //免费局结束
                        if (!empty($tempData['bl'])) {
                            // var_dump('free----------------------------end');
                            $aw = $tempData['bl'] - $this->bl;
                            if ($body1['game_code'] == 2002005) {
                                $aw = $aw / 1000;
                            }
                            $this->bl = $tempData['bl'];
                            $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                            // echo sprintf('--free, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $bl, $result['dt']['bl'], 0, $aw);
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    protected function writeBinaryData($binaryWriter, $data)
    {
        $postBody = '';
        $binaryWriter->clear();
        switch ($data['game_code']) {
            case 2002002: // 虎
                $postBody = $binaryWriter->writeString($data['atk'])
                    ->writeInt64($data['bet'])
                    ->writeInt($data['mult'])
                    ->getBuffer();
                break;
            default:
                $postBody = $binaryWriter->writeString($data['atk'])
                    ->writeInt64($data['bet'])
                    ->writeInt($data['game_type'])
                    ->writeInt($data['mult'])
                    ->getBuffer();
                break;
        }
        return $postBody;
    }

    protected function readBinaryData($result, $data)
    {
        $ret = [];
        if (empty($result)) return $ret;

        $binaryReader = new BinaryStreamReader($result);
        switch ($data['game_code']) {
            case 2002002: // 虎
                $ret = [
                    'bl' => $binaryReader->readInt64(),
                    'formation' => $binaryReader->readString(),
                    'free' => $binaryReader->readBool(),
                    'fakeFree' => $binaryReader->readBool(),
                    'Special' => $binaryReader->readBool(),
                ];
                break;
            case 2002005: // 马
                $ret = [
                    'bl' => $binaryReader->readInt64(),
                    'formation' => $binaryReader->readString(),
                    'free' => $binaryReader->readBool(),
                    'fakeFree' => $binaryReader->readBool(),
                ];
                break;
            default:
                $ret = [
                    'bl' => $binaryReader->readInt64(),
                    'formation' => $binaryReader->readString(),
                    'free' => $binaryReader->readBool(),
                ];
                break;
        }
        return $ret;
    }

    /**
     * g759任务
     */
    protected function g759Task($url, $body, $i, $redis, $logId, $nums)
    {
        //成功计数
        $flag = 0;
        //每轮的局数冗余请求次数防止请求达不到配置的局数
        $ext = 1000;
        $fails = 0; // 失败次数
        $msg = '';
        $cacheKey = sprintf('log:%s:rounds:%s', $logId, $i);
        foreach (range(1, $nums + $ext) as $no) {
            try {
                if ($flag >= $nums) {
                    return;
                }

                if ($fails >= $this->errLimit) {
                    break;
                }

                $result = wget($url, $body, [CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']]);
                // var_dump(json_encode($result));
                if (empty($result['dt']) || !empty($result['err'])) {
                    // if ($no >= $nums) return;
                    $fails++;
                    $msg = !empty($result['err']) ? json_encode($result['err'])  : 'dt为空';
                    continue;
                }

                $data = $result['dt']['si'];
                $aw = $data['aw'];
                $tbb = $data['tbb'];

                // 重置连续失败次数
                $fails = 0;

                //按轮数累计
                $redis->handler()->hIncrByFloat($cacheKey, 'aw', $aw);
                $redis->handler()->hIncrByFloat($cacheKey, 'tbb', $tbb);
                // echo sprintf('round: %s, last_bl: %s, now_bl: %s, tbb: %s, aw: %s' . PHP_EOL, $i, $bl, $data['bl'], $tbb, $aw);
                $redis->handler()->expire($cacheKey, $this->cacheTime);
                $flag++;
                
            } catch (\Exception $e) {
                $fails++;
                $msg = $e->getMessage();
                // var_dump($e->getMessage());
                save_log('game_data_job_error_in', json_encode([
                    'log_id' => $logId,
                    'i' => $i,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        //结果少于配置局数删除
        if ($flag < $nums) {
            $redis->handler()->del($cacheKey);
        }

        if ($fails >= $this->errLimit) {
            throw new \Exception(sprintf('连续请求失败次数超过%s次，失败信息：%s', $this->errLimit, $msg));
        }
        return;
    }

    /**
     * 统计命令
     *
     * @return void
     */
    protected function doDataStore($fix)
    {
        if ($fix) {
            Console::call('action', ['backend/Stat/fixData']);
            return;
        }

        Console::call('action', ['backend/Stat/runData']); //放在crontab 处理
    }

    /**
     * 刷新atk
     */
    protected function refreshAtk() {}
}
