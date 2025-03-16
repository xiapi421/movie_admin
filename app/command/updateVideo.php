<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\video\Category;
use think\facade\Log;

class updateVideo extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('updateVideo')
            ->setDescription('the updateVideo command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输
        $output->writeln('开始更新json'.date('Y-m-d H:i:s'));
        $save_root_path = app()->getRootPath() . 'public/storage/video';
        $allIds = Db::name('videos')->order('total_purchases desc')->column('id');
        $hot_path = $save_root_path . '/hot';
        $random_hot_path = $save_root_path . '/randomhot';
        if (!file_exists($hot_path)) {
            mkdir($hot_path, 0755, true);
        }
        if (!file_exists($random_hot_path)) {
            mkdir($random_hot_path, 0755, true);
        }

        $categories = Category::where('status', 1)->field('id,name,hot,random')->order('weigh desc')->select();

        // 获取固定的800个热门视频
        $fixedHotVideos = [];
        $each_category_hot = [];
        foreach ($categories as $category) {
            if ($category['hot'] > 0) {
                $categoryVideos = Db::name('videos')
                    ->where("video_category_ids", $category['id'])
                    ->order('total_purchases', 'desc')
                    ->limit($category['hot'])
                    ->field('id,name,image,duration,views')
                    ->select()
                    ->toArray();
                $fixedHotVideos = array_merge($fixedHotVideos, $categoryVideos);
                $each_category_hot[$category['id']] = array_column($categoryVideos, 'id');
            }
        }

        $hotIds = array_column($fixedHotVideos, 'id');
        // $hotv = Db::name('videos')
        //     ->whereIn('id', $hotIds)
        //     ->field('id,name,image,duration,views')
        //     ->select();
        // $remainingIds = array_diff($allIds, $hotIds);
        $excludeIds = array_column($fixedHotVideos, 'id'); // 排除已经在固定热门中的视频
        for ($i = 1; $i <= 1000; $i++) {
            // $randomKeys = array_rand($remainingIds, 200);
            // $randomIds = array_map(function ($k) use ($remainingIds) {
            //     return $remainingIds[$k];
            // }, $randomKeys);
            // $hot_videos = Db::name('videos')
            //     ->whereIn('id', $randomIds)
            //     ->field('id,name,image,duration,views')
            //     ->select();

            $randomVideos = [];
            // $excludeIds = array_column($fixedHotVideos, 'id'); // 排除已经在固定热门中的视频
            foreach ($categories as $category) {
                if ($category['random'] > 0) {
                    
                    $categoryRandomVideos = Db::name('videos')
                        ->where("video_category_ids", $category['id'])
                        ->whereNotIn('id', $each_category_hot[$category['id']])
                        // ->orderRaw('RAND()')
                        ->limit($category['random'])
                        ->field('id,name,image,duration,views')
                        ->select()
                        ->toArray();
                    $randomVideos = array_merge($randomVideos, $categoryRandomVideos);
                }
            }

            $rv = array_merge($fixedHotVideos, $randomVideos);
            shuffle($rv);
            file_put_contents($hot_path . "/$i.json", json_encode($rv, JSON_UNESCAPED_UNICODE));
        }
        //再处理大随机
        for ($i = 1; $i <= 1000; $i++) {
            $randomKeys = array_rand($allIds, 1000);
            $randomIds = array_map(function ($k) use ($allIds) {
                return $allIds[$k];
            }, $randomKeys);
            $random_videos = Db::name('videos')
                ->whereIn('id', $randomIds)
                ->field('id,name,image,duration,views')
                ->select();
            file_put_contents($random_hot_path . "/$i.json", json_encode($random_videos, JSON_UNESCAPED_UNICODE));
        }
        // 处理其它分类

        $category_path = $save_root_path . '/category.json';
        file_put_contents($category_path, json_encode($categories, JSON_UNESCAPED_UNICODE));
        foreach ($categories as $category) {
            // 确保分类目录存在
            //全部免生成
            if ($category['name'] == '全部') {
                continue;
            }
            $cid = $category['id'];
            $category_path = $save_root_path . '/' . $cid;
            if (!file_exists($category_path)) {
                mkdir($category_path, 0755, true);
            }
            if ($category['name'] == '今日新片') {
                $category_ids = Db::name('videos')->order('create_time', 'desc')->limit(500)->column('id');
            } else {
                $category_ids = Db::name('videos')
                    ->whereRaw("FIND_IN_SET('$cid', video_category_ids) > 0")
                    ->order('total_purchases', 'desc')
                    ->column('id');
            }
            for ($j = 1; $j <= 100; $j++) {
                $category_filename = $category_path . '/' . $j . '.json';
                if (count($category_ids) <= 300) {
                    $videos = Db::name('videos')->field('id,name,image,duration,views')
                        ->whereIn('id', $category_ids)->select();
                    file_put_contents($category_filename, json_encode($videos, JSON_UNESCAPED_UNICODE));
                } else {
                    $two = array_slice($category_ids, 0, 200);
                    $three = array_diff($category_ids, $two); //剩余的Id
                    $randomKeys = array_rand($three, 100);
                    $four = array_map(function ($k) use ($three) {
                        return $three[$k];
                    }, $randomKeys);
                    $ca_real_ids = array_merge($two, $four);
                    $videos = Db::name('videos')->field('id,name,image,duration,views')
                        ->whereIn('id', $ca_real_ids)->select();
                    file_put_contents($category_filename, json_encode($videos, JSON_UNESCAPED_UNICODE));
                }
            }



            Log::write($cid, 'info');
            // 获取分类下所有视频
            $videos = Db::name('videos')
                ->whereRaw("FIND_IN_SET('$cid', video_category_ids) > 0")
                ->field('id,name,image,duration')
                ->select();
            //            if (count($videos) < 300) {}
            file_put_contents($category_filename, json_encode($videos, JSON_UNESCAPED_UNICODE));
        }
        $output->writeln('更新json完成'.date('Y-m-d H:i:s'));
    }
}
