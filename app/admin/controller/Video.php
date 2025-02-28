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
            'list'   => $res->items(),
            'total'  => $res->total(),
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
                    'name'               => $title,
                    'image'              => $img,
                    'url'           => $m3u8,
                    'duration'           => $duration,
                    'video_category_id'  => $categoryModel->id,
                    'create_time'        => time(),
                    'update_time'        => time()
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
        $save_root_path = app()->getRootPath().'public/storage/video';
        //写分类json
        $categorys = Category::query()->select();
        $category_path = $save_root_path.'/category.json';
        $category_json = json_encode($categorys,JSON_UNESCAPED_UNICODE);
        file_put_contents($category_path,$category_json);
//        $ca_type = ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19'];
        foreach ($categorys as $value) {
            $data = $this->model->where('video_category_id',$value['id'])->field('id,video_category_id,name,image,duration,create_time')->order('total_purchases','desc')->select();
//            $cree_path = $save_root_path."/ca_".$key;
//            if (!is_dir($cree_path)) {
//                mkdir($cree_path,0755,true);
//            }
            $category_path = $save_root_path."/ca_".$value['id'].".json";
            $json = json_encode($data,JSON_UNESCAPED_UNICODE);
            file_put_contents($category_path,$json);
        }

        //热门json
        $hot_data = $this->model->field('id,video_category_id,name,image,duration,create_time')->order('total_purchases','desc')->limit(1000)->select();
        $hot_path = $save_root_path.'/hot.json';
        $hot_json = json_encode($hot_data,JSON_UNESCAPED_UNICODE);
        file_put_contents($hot_path,$hot_json);
        $this->success('');
    }

    public function autoUpdateJson()
    {
        
    }

    public function clearHot()
    {

    }
}