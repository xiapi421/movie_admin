<?php
declare (strict_types=1);

namespace app\command;

use app\admin\model\Videos;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;

class Hello extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('hello')
            ->setDescription('the hello command');
    }

    protected function execute(Input $input, Output $output)
    {
        $today = date('Ymd');
        // 指令输出
        $amount = Db::name('videos')->count();
        for ($i = 1; $i <= $amount; $i++) {
            $total_view = Cache::store('redis')->get('vid:' . $i . ":view", '0');
            $total_click = Cache::store('redis')->get('vid:' . $i . ":click", '0');
            $total_purchases = Cache::store('redis')->get('vid:' . $i . ":purchases", '0');

            $today_view = Cache::store('redis')->get('vid:' . $i . ':' . $today . ":view", '0');
            $today_click = Cache::store('redis')->get('vid:' . $i . ':' . $today . ":click", '0');
            $today_purchases = Cache::store('redis')->get('vid:' . $i . ':' . $today . ":purchases", '0');
            if ($total_purchases==0 ||  $today_click==0 ) {
                $total_conversion_rate =0;
            }else{
                $total_conversion_rate = $total_purchases/$total_click*100;
            }

            if ($today_purchases==0 || $today_click==0){
                $today_conversion_rate =0;
            }else{
                $today_conversion_rate = $today_purchases/$today_click*100;
            }
            Db::name('videos')->where('id', $i)->update([
                    'total_views' => $total_view,
                    'total_clicks' => $total_click,
                    'total_purchases' => $total_purchases,
                    'total_conversion_rate' => $total_conversion_rate,
                    'today_views' => $today_view,
                    'today_clicks' => $today_click,
                    'today_purchases' => $today_purchases,
                    'today_conversion_rate' => $today_conversion_rate,
                ]
            );
        }
        echo '更新视频统计信息完成'.date('Y-m-d H:i:s')."\n";
    }
}
