<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\helper\Str;
use ba\Bce;
use think\facade\Cache;
use Throwable;
use think\facade\Db;
/**
 * 落地页管理
 */
class Lading extends Backend
{
    /**
     * Lading模型对象
     * @var object
     * @phpstan-var \app\admin\model\Lading
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Lading();
    }
    public function random()
    {
        //只能包含小写字母、数字和“-”，开头结尾为小写字母和数字，长度在4-63之间
        $bucketName =Str::lower( Str::random(20));
        $filename = Str::lower( Str::random(20)).'.html';
        $zzfilename = Str::lower( Str::random(20)).'.html';
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
        $result = $bce->uploadFile($bucketName, $zzfilename, root_path() . 'public/zz.html');
        if($result['code']!=200) $this->error('生成失败');
        if($result['code'] == 200){
            $ldurl = 'https://'.$bucketName.'.bj.bcebos.com/'.$filename;
            $zzurl = 'https://'.$bucketName.'.bj.bcebos.com/'.$zzfilename;
            $this->model->create([
                'bucket' => $bucketName,
                'ldurl'=>$ldurl,
                'zzurl'=>$zzurl,
                'create_time' => time(),
                'status'=>1
            ]);
            // Db::name('config')->where('name','zzurl')->update(['value'=>$zzurl]);
            // Db::name('config')->where('name','ldurl')->update(['value'=>$ldurl]);
            Cache::store('redis')->set('zzurl',$zzurl);
            Cache::store('redis')->set('ldurl',$ldurl);
            $this->success('生成成功',  ['bucketName' => $bucketName, 'ldurl' => $ldurl, 'zzurl' => $zzurl]);
        }else{
            $this->error('生成失败');
        }
    }

    public function del(): void
    {
        $bce = new Bce([
            'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
            'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
        ]);
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
                $bce->deleteFile($v['bucket'], str_replace('https://'.$v['bucket'].'.bj.bcebos.com/','',$v['ldurl']));
                $bce->deleteFile($v['bucket'], str_replace('https://'.$v['bucket'].'.bj.bcebos.com/','',$v['zzurl']));
                $bce->deleteBucket($v['bucket']);
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
}