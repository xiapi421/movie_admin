<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\helper\Str;
use ba\Bce;
use Throwable;


/**
 * 代理链接
 */
class Link extends Backend
{
    /**
     * Link模型对象
     * @var object
     * @phpstan-var \app\admin\model\Link
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Link();
    }
    public function random()
    {
        //只能包含小写字母、数字和“-”，开头结尾为小写字母和数字，长度在4-63之间
        $bucketName =Str::lower( Str::random(20));
        $filename = Str::lower( Str::random(20)).'.html';
        $bce = new Bce([
            'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
            'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
        ]);
        $result = $bce->createBucket($bucketName);
        if($result['code']!=200) $this->error('生成失败');
        $result= $bce->setBucketAcl($bucketName, 'public-read');
        if($result['code']!=200) $this->error('生成失败');
        $result = $bce->uploadFile($bucketName, $filename, root_path() . 'public/front.html');
        if($result['code']!=200) $this->error('生成失败');
        if($result['code'] == 200){
            $this->model->create([
                'bucket' => $bucketName,
                'filename' => $filename,
                'remark'=>'https://'.$bucketName.'.bj.bcebos.com/'.$filename,
                'create_time' => time(),
            ]);
            $this->success('生成成功',  ['bucketName' => $bucketName, 'filename' => $filename]);
        }else{
            $this->error('生成失败');
        }
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
                $count += $v->delete();
            }
            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $bce = new Bce([
                'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
                'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
            ]);
            $bce->deleteFile($v['bucket'], $v['filename']);
            $bce->deleteBucket($v['bucket']);
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
        $ids = input('post.ids');
        $ids = explode(',', $ids);
        $links = $this->model->where('id', 'in', $ids)->select();
        $result = [];
        foreach ($links as $link) {
            $url = urlencode($link['url']);
            $info = wxCheckUrl($url);
            $result[] = ['id' => $link['id'],'url' => urldecode($url), 'user_id' => $link['user_id'],'info'=>$info['info']];
        }
        $this->success('ok', $result);
    }
}