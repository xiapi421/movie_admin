<?php

namespace app\admin\controller;

use app\admin\model\video\Category;
use think\facade\Db;
use Throwable;
use app\common\controller\Backend;

/**
 * 视频管理
 */
class Video extends Backend
{
    /**
     * Video模型对象
     * @var object
     * @phpstan-var \app\admin\model\Video
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected array $withJoinTable = ['videoCategory'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Video();
    }

    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {
        // 如果是 select 则转发到 select 方法，若未重写该方法，其实还是继续执行 index
        if ($this->request->param('select')) {
            $this->select();
        }

        /**
         * 1. withJoin 不可使用 alias 方法设置表别名，别名将自动使用关联模型名称（小写下划线命名规则）
         * 2. 以下的别名设置了主表别名，同时便于拼接查询参数等
         * 3. paginate 数据集可使用链式操作 each(function($item, $key) {}) 遍历处理
         */
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        $res->visible(['videoCategory' => ['name']]);

        $this->success('', [
            'list' => $res->items(),
            'total' => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

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

            // 验证分类是否存在
            $categoryModel = Category::where('name', $category)->find();
            if (!$categoryModel) {
                throw new \Exception("分类不存在: $category");
            }

            // 构建数据
            $videoData = [
                'name' => $title,
                'image' => $img,
                'url' => $m3u8,
                'duration' => $duration,
                'video_category_id' => $categoryModel->id,
                'create_time' => time(),
                'update_time' => time()
            ];

            $this->model->insert($videoData);
            $successCount++;
        }
        $this->success("成功导入 {$successCount} 条视频数据");

    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */

    public function updateJson()
    {
        $save_root_path = app()->getRootPath() . 'public/storage/video';
        $allIds = $this->model->column('id');
        shuffle($allIds);

        // 分页处理（每页1000个）
        $chunks = array_chunk($allIds, 1000);
        Db::name('config')->where('name', 'hot_pages')->update(['value' => count($chunks)]);
        foreach ($chunks as $page => $idChunk) {
            // 随机获取当前分页的视频（保证不重复）
            $videos = $this->model->field('id,name,image,duration')
                ->whereIn('id', $idChunk)
                ->orderRaw('FIELD(id, ' . implode(',', $idChunk) . ')')
                ->select();
            // 生成分页文件
            $num = $page + 1;
            file_put_contents("$save_root_path/hot_{$num}.json", json_encode($videos, JSON_UNESCAPED_UNICODE));
        }

        $hot_videos = $this->model->field('id,name,image,duration')
            ->order('total_purchases', 'desc')
            ->limit(1000)
            ->select();
        // 生成首页视频
        file_put_contents($save_root_path . '/hot.json', json_encode($hot_videos, JSON_UNESCAPED_UNICODE));


        // 处理其它分类
        $categories = Category::where('status', 1)->field('id,name')->order('weigh desc')->select();
        $category_path = $save_root_path . '/category.json';
        file_put_contents($category_path, json_encode($categories, JSON_UNESCAPED_UNICODE));
        foreach ($categories as $category) {
            // 确保分类目录存在
            $category_path = $save_root_path . '/' . $category['id'] . '.json';

            // 获取分类下所有视频
            $videos = $this->model
                ->where('video_category_id', $category['id'])
                ->field('id,name,image,duration')
                ->select();
            file_put_contents($category_path, json_encode($videos, JSON_UNESCAPED_UNICODE));
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