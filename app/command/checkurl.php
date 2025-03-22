<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\admin\model\Link;

class checkurl extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('checkurl')
            ->setDescription('检查链接是否有效');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $links = Link::where('check_status', '0')->select();
        foreach ($links as $link) {
            $res = wxCheckUrl($link['url']);
            if ($res['status'] != 1) {
                $output->writeln($link['url']);
                $link->save(['check_status' => $res['status'],'info'=>$res['info']]);
            }
            if($res['status'] < 0){
                $output->writeln($link['url']);
                $link->save(['check_status' => 0]);
            }
        }
        $output->writeln('app\command\checkurl');
    }
}
