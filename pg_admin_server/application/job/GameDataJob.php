<?php

namespace app\job;

use think\Cache;
use think\Console;
use think\Db;

class GameDataJob
{
    protected $dataStore = [];

    protected $susNum = 0;

    protected $errNum = 0;

    public function fire($job, $data)
    {
        set_time_limit(0);
        try {
            $batch = $data['batch'];
            $per = $data['per'];
            $end = $data['end'];
            $logId = $data['log_id'];

            $start = ($batch - 1) * $per + 1;
            if ($end) {
                $end = $start + $end - 1;
            } else {
                $end = $batch * $per;
            }

            $redis = Cache::store('redis');

            $url = $data['url'];
            $body = $data['body'];
            for ($i = $start; $i <= $end; $i++) {
                $this->runTask($url, $body, $i, $redis, $logId);
            }

            Db::table('game_run_stat')->where('id', $logId)->inc('err', $this->errNum)->update(['queue_' . $batch => 1]);

            // 任务处理完成后，删除任务
            $job->delete();

            $this->doDataStore();
        } catch (\Throwable $t) {
            save_log('game_data_job_error_out', json_encode([
                'log_id' => $logId,
                'batch' => $batch,
                'error' => $t->getMessage(),
            ]));
        }
        
    }

    public function failed($data)
    {
        var_dump('任务达到最大重试次数后，失败处理');
    }

    protected function runTask($url, $body, $i, $redis, $logId)
    {
        try {
            $result = wget($url, http_build_query($body));
            //接口请求错误
            if (empty($result['dt']) || !empty($result['err'])) {
                throw new \Exception('err -> ' . $result['err']['msg'] ?? '');
            }

            $data = $result['dt']['si'];
            $this->susNum++;
            if ($data['nst'] != 1) return; //只记录nst=1的数据（新的一局）

            //按psid累计
            $cacheKey = sprintf('log:%s:psid:%s', $logId, $data['psid']);
            $redis->handler()->incrByFloat($cacheKey, $data['tbb'] ? round($data['aw'] / $data['tbb'], 4) : 0);
            $redis->handler()->expire($cacheKey, 300);

        } catch (\Exception $e) {
            $this->errNum++;
            save_log('game_data_job_error_in', json_encode([
                'log_id' => $logId,
                'i' => $i,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * 统计命令
     *
     * @return void
     */
    protected function doDataStore()
    {
        Console::call('action', ['backend/Stat/runData']);
    }

    /**
     * 刷新atk
     */
    protected function refreshAtk() {}
}
