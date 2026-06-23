<?php

namespace app\job;

use think\Cache;
use think\Console;
use think\Db;

// 游戏返奖
class GameSpinJob
{

    public function fire($job, $data)
    {
        set_time_limit(0);
        try {
            $url = $data['url'];
            $post_data = $data['post_data'];
            unset($post_data['type_id']);
            $this->doSpin($url, $post_data);
        } catch (\Throwable $t) {
            save_log('game_spin_error', json_encode([
                'data' => $data,
                'error' => $t->getMessage(),
            ]));
        } finally {
            // 任务处理完成后，删除任务
            $job->delete();
        }
    }

    public function failed($data)
    {
        var_dump('任务达到最大重试次数后，失败处理');
    }

    public function doSpin($url, $post_data = '', $header = [], $type = 'post')
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
}
