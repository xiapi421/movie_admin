<?php

namespace app\admin\controller;

use app\admin\model\video\Category;
use app\common\controller\Backend;
use think\facade\Db;
use think\facade\Log;

/**
 * 视频管理b
 */
class Videos extends Backend
{
    /**
     * Videos模型对象
     * @var object
     * @phpstan-var \app\admin\model\Videos
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Videos();
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
    public function importVideo()
    {
        $file = $this->request->file('file');
        if (!$file) {
            $this->error('请选择要上传的文件');
        }


//            $file->validate(['size' => 10485760, 'ext' => 'txt,csv'])->check();

        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            throw new \Exception('文件内容为空');
        }

        $successCount = 0;
        foreach ($lines as $line) {
            $fields = explode('|', trim($line));
            if (count($fields) < 5) {
                throw new \Exception("格式错误: $line");
            }

            // 字段映射
            list($title, $img, $m3u8, $duration, $category) = $fields;
            // 构建数据
            $videoData = [
                'name' => $title,
                'image' => $img,
                'url' => $m3u8,
                'duration' => $duration,
                'video_category_ids' => $category,
                'create_time' => time(),
                'update_time' => time()
            ];

            $this->model->insert($videoData);
            $successCount++;
        }
        $this->success("成功导入 {$successCount} 条视频数据");

    }
    public function updateJson()
    {
        $save_root_path = app()->getRootPath() . 'public/storage/video';
        $allIds = Db::name('videos')->order('total_purchases desc')->column('id');
        $hot_path= $save_root_path . '/hot' ;
        $random_hot_path = $save_root_path . '/randomhot' ;
        if (!file_exists($hot_path)) {
            mkdir($hot_path, 0755, true);
        }
        if (!file_exists($random_hot_path)) {
            mkdir($random_hot_path, 0755, true);
        }

//        // 分页处理（每页1000个）
//        $chunks = array_chunk($allIds, 1000);
//        Db::name('config')->where('name', 'hot_pages')->update(['value' => count($chunks)]);
//        foreach ($chunks as $page => $idChunk) {
//            // 随机获取当前分页的视频（保证不重复）video_category_ids,
//            $videos = Db::name('videos')
//                ->whereIn('id', $idChunk)
//                ->field('id,name,image,duration')
//                ->orderRaw('FIELD(id, ' . implode(',', $idChunk) . ')')
//                ->select();
//            // 生成分页文件
//            $num = $page + 1;
//            file_put_contents("$save_root_path/hot_{$num}.json", json_encode($videos, JSON_UNESCAPED_UNICODE));
//        }
        //先处理热门

        $hotIds = array_slice($allIds, 0, 800);
        $hotv = $hot_videos = Db::name('videos')
            ->whereIn('id', $hotIds)
            ->field('id,name,image,duration')
            ->select();
        $remainingIds = array_diff($allIds, $hotIds);
        for ($i = 1; $i <= 1000; $i++) {
            $randomKeys = array_rand($remainingIds, 200);
            $randomIds = array_map(function($k) use ($remainingIds) {
                return $remainingIds[$k];
            }, $randomKeys);
            $hot_videos = Db::name('videos')
                ->whereIn('id', $randomIds)
                ->field('id,name,image,duration')
                ->select();
            $rv = array_merge($hotv->toArray(),$hot_videos->toArray());
            file_put_contents($hot_path . "/$i.json", json_encode($rv, JSON_UNESCAPED_UNICODE));

        }
        //再处理大随机
        for ($i = 1; $i <= 1000; $i++) {
            $randomKeys = array_rand($allIds, 1000);
            $randomIds = array_map(function($k) use ($allIds) {
                return $allIds[$k];
            }, $randomKeys);
            $random_videos = Db::name('videos')
                ->whereIn('id', $randomIds)
                ->field('id,name,image,duration')
                ->select();
            file_put_contents($random_hot_path . "/$i.json", json_encode($random_videos, JSON_UNESCAPED_UNICODE));

        }
        // 处理其它分类
        $categories = Category::where('status', 1)->field('id,name')->order('weigh desc')->select();
        $category_path = $save_root_path . '/category.json';
        file_put_contents($category_path, json_encode($categories, JSON_UNESCAPED_UNICODE));
        foreach ($categories as $category) {
            // 确保分类目录存在
            $cid= $category['id'];
            $category_path= $save_root_path . '/' . $cid;
            if (!file_exists($category_path)) {
                mkdir($category_path, 0755, true);
            }

            $category_ids =Db::name('videos')
                ->whereRaw("FIND_IN_SET('$cid', video_category_ids) > 0")
                ->order('total_purchases', 'desc')
                ->column('id');
            for ($j = 1; $j <=100 ; $j++) {
                $category_filename = $category_path . '/' . $j . '.json';
                if (count($category_ids) <=300) {
                    $videos = Db::name('videos')->field('id,name,image,duration')
                        ->whereIn('id', $category_ids)->select();
                    file_put_contents($category_filename, json_encode($videos, JSON_UNESCAPED_UNICODE));
                }else{
                    $two =array_slice($category_ids, 0, 200);
                    $three = array_diff($category_ids, $two);//剩余的Id
                    $randomKeys = array_rand($three, 100);
                    $four = array_map(function($k) use ($three) {
                        return $three[$k];
                    }, $randomKeys);
                    $ca_real_ids=array_merge($two,$four);
                    $videos = Db::name('videos')->field('id,name,image,duration')
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
        $this->success('更新成功');
    }

    public function autoUpdateJson()
    {

    }

    public function clearHot()
    {

    }

    public function updateImageDomain()
    {
        $new_domain = $this->request->param('domain');
        $video = $this->model->where('id', '1')->find();
        //使用正则表达式提取出域名
        $old_domain = preg_match('/https?:\/\/([^\/]+)/', $video->image, $matches);
        $old_domain = $matches[0];
        Db::execute("UPDATE ba_video SET image = REPLACE(image, '$old_domain', '$new_domain')");
//        $this->updateJson();
        $this->success('更新成功');
    }

    public function updateVideoDomain()
    {
        $new_domain = $this->request->param('domain');
        $video = $this->model->where('id', '1')->find();
        //使用正则表达式提取出域名
        $old_domain = preg_match('/https?:\/\/([^\/]+)/', $video->url, $matches);
        $old_domain = $matches[0];
        Db::execute("UPDATE ba_video SET url = REPLACE(url, '$old_domain', '$new_domain')");
//        $this->updateJson();
        $this->success('更新成功', [$matches, $video]);
    }
}