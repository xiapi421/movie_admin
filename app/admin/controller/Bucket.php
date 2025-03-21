<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use Throwable;
use ba\Bce;
use app\admin\model\Baiduyun as BaiduyunModel;
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

    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->field($this->indexField)
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->where('area','!=','bj')
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    public function del(): void
    {
        $where             = [];
        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds) {
            $where[] = [$this->dataLimitField, 'in', $dataLimitAdminIds];
        }

        $ids     = $this->request->param('ids/a', []);
        $where[] = [$this->model->getPk(), 'in', $ids];
        $data    = $this->model->where($where)->select();

        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
                $bce = new Bce([
                    'accessKeyId' => $v['apiKey'],
                    'secretAccessKey' => $v['secret'],
                ]);
                $bce->deleteFile($v['name'],$v['filename']);
                $bce->deleteBucket($v['name']);
                $count += $v->delete();
            }
            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
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
            $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => $info['status'],'info'=>$info['info']];
            // if($info=='域名正常'){
            //     $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => 1];
            // }else{
            //     $result[] = ['id' => $bucket['id'],'url' => $url, 'status' => 0];
            // }
        }
        $this->success('ok', $result);
    }

    public function checkBaidu()
    {
        $buckets = $this->model->where('area', 'bj')->select();
        $result = [];   
        foreach ($buckets as $bucket) {
            
            $url = urlencode($bucket['url']);
            // $this->success($url, $result);
            $info = wxCheckUrl($url);
            if($info['status']!=1){
               $result[] = ['id' => $bucket['id'],'url' => urldecode($url), 'status' => $bucket['user_id'],'info'=>$info['info']];
            }
        }
        $this->success('ok', $result);
    }

    public function deleteBaidu ()
    {
        $ids = input('post.ids');
        $ids = explode(',', $ids);
        $buckets = $this->model->where('id', 'in', $ids)->select();
        foreach ($buckets as $bucket) {
            BaiduyunModel::where('apiKey', $bucket['apiKey'])->setDec('used',1);
            $bce = new Bce([
                'accessKeyId' => $bucket['apiKey'],
                'secretAccessKey' => $bucket['secret'],
            ]);
            $bce->deleteFile($bucket['name'],$bucket['filename']);
            $bce->deleteBucket($bucket['name']);    
            $bucket->delete();
        }
        $this->success('ok');
    }
}