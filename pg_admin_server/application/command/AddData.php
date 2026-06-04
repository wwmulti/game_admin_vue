<?php

namespace app\command;

use app\common\model\database1\TigerBetHistory;
use think\console\Input;
use think\console\Output;

class AddData extends \think\console\Command
{
    protected function configure()
    {
        $this->setName('add_data_cron')
            ->setDescription('添加测试数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('statistics reset_data Crontab job start;');
            $array = [];
            for ($i1=0;$i1<1000;$i1++){
                for ($i=0;$i<10000;$i++){
                    $yesterday = "2024-08-".rand(1,31);
                    $startTime = $yesterday.' '.rand(0,23).':'.rand(0,59).':'.rand(0,59);
                    $startStampTime = strtotime($startTime);
                    $array[] = [
                        'platform_id'=>1,
                        'merchant_id'=>8,
                        'user_id'=>rand(1,45),
                        'sid'=>$startStampTime.rand(100,999).rand(100,999),
                        'psid'=>$startStampTime.rand(100,999).rand(100,999),
                        'detail'=>'',
                        'bet'=>rand(1,999)*1000,
                        'transfer'=>rand(1,999)*1000*rand(-1,1),
                        'created_at'=>$startStampTime.rand(100,999)
                    ];
                }
                TigerBetHistory::insertAll($array);
            }
        $output->writeln('statistics reset_data Crontab job end;');
    }
}