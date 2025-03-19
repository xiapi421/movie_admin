<?php
declare (strict_types = 1);

namespace app\command;

use app\admin\model\Pay;
use app\common\model\User;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;

class Tongji extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('tongji')
            ->setDescription('the tongji command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $users = User::where('id', '>', 0)->select();
        foreach ($users as $user) {
            $user->save([
                'lastday_sell'=>Cache::store('redis')->get('agent:'.$user['id'].':'.date('Ymd',strtotime('-1 day')).':total_sell',0),
                'lastday_money'=>$user['money'],
                'today_order'=>0,
                'today_money'=>0,
            ]);
        }
        Cache::store('redis')->set('total:'.date('Ymd',strtotime('-1 day')).':total_agent_money', User::where('status', 1)->sum('money'), 0);

        //支付通道统计
        $pays = Pay::where('id', '>', 0)->select();
        foreach ($pays as $pay) {
            $pay->save([
                'lastday_order'=>$pay['today_order'],
                'lastday_money'=>$pay['today_money'],
                'today_order'=>0,
                'today_money'=>0,
            ]);
        }
        echo "统计完成".date('Y-m-d H:i:s')."\n";
    }
}
