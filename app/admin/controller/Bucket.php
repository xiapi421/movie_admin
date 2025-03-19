<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 储存桶管理
 */
class Bucket extends Backend
{
    /**
     * Bucket模型对象
     * @var object
     * @phpstan-var \app\admin\model\Bucket
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Bucket();
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */


    public function checkUrl()
    {
        $ids = input('post.urls');
        $ids = explode(',', $ids);
        $buckets = $this->model->where('id', 'in', $ids)->select();
        $result = [];
        foreach ($buckets as $bucket) {
            if($bucket['area']=='bj'){
                $url = 'https://'.$bucket['name'].'.bj.bcebos.com';
            }else{
                $url = 'https://'.$bucket['area'].'.myqcloud.com/'.$bucket['name'];
            }
            $info = wxCheckUrl($url);
            $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => $info];
            // if($info=='域名正常'){
            //     $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => 1];
            // }else{
            //     $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => 0];
            // }
        }
        $this->success('ok', $result);
    }
}