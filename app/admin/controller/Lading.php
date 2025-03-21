<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\helper\Str;
use ba\Bce;
use think\facade\Cache;
use Throwable;
use think\facade\Db;
use Qcloud\Cos\Client as CosClient;
use Ramsey\Uuid\Uuid;
use app\admin\model\Bucket;

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
        $bucketName = Str::lower(Str::random(20));
        $ldfilename = Str::lower(Str::random(20)) . '.html';
        $zzfilename = Str::lower(Str::random(20)) . '.html';
        $bce = new Bce([
            'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
            'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
        ]);
        $result = $bce->createBucket($bucketName);
        if ($result['code'] != 200) $this->error('生成失败');
        $result = $bce->setBucketAcl($bucketName, 'public-read');
        if ($result['code'] != 200) $this->error('生成失败');
        $result = $bce->uploadFile($bucketName, $ldfilename, root_path() . 'public/front.html');
        if ($result['code'] != 200) $this->error('生成失败');
        $result = $bce->uploadFile($bucketName, $zzfilename, root_path() . 'public/zz.html');
        if ($result['code'] != 200) $this->error('生成失败');
        $ldurl = 'https://' . $bucketName . '.bj.bcebos.com/' . $ldfilename;
        $zzurl = 'https://' . $bucketName . '.bj.bcebos.com/' . $zzfilename;
        $this->model->create([
            'bucket' => $bucketName,
            'ldurl' => $ldurl,
            'zzurl' => $zzurl,
            'create_time' => time(),
            'status' => 1
        ]);

        Cache::store('redis')->set('zzurl', $zzurl);
        Cache::store('redis')->set('ldurl', $ldurl);


        $txbucketName = get_sys_config('tx_name');
        $txarea = get_sys_config('tx_area');
        $cosClient = new CosClient(
            array(
                'region' => $txarea,
                'scheme' => 'https', //协议头部，默认为 http
                'credentials' => array(
                    'secretId'  => get_sys_config('tx_apikey'),
                    'secretKey' => get_sys_config('tx_secret')
                )
            )
        );
        $cosClient->upload(
            $txbucketName,
            $zzfilename,
            file_get_contents(root_path() . 'public/zz.html'),
            array(
                'Content-Type' => 'text/html'
            )
        );

        $cosClient->upload(
            $txbucketName,
            $ldfilename,
            file_get_contents(root_path() . 'public/front.html'),
            array(
                'Content-Type' => 'text/html'
            )
        );
        $txzzurl = 'https://cos.' . $txarea . '.myqcloud.com/' . $txbucketName . '/' . $zzfilename;
        $txldurl = 'https://cos.' . $txarea . '.myqcloud.com/' . $txbucketName . '/' . $ldfilename;
        $this->model->create([
            'bucket' => $txbucketName,
            'ldurl' => $txldurl,
            'zzurl' => $txzzurl,
            'create_time' => time(),
            'status' => 1
        ]);
        Cache::store('redis')->set('txzzurl', $txzzurl);
        Cache::store('redis')->set('txldurl', $txldurl);
        $this->success('生成成功',  ['bucketName' => $bucketName, 'ldurl' => $txldurl, 'zzurl' => $txzzurl]);
    }

    // public function txRandom(){
    //     $bucket_ids = Bucket::where('status', '1')->where('category', 'like', '%腾讯%')->column('id');
    //     $bucket_id = $bucket_ids[array_rand($bucket_ids)];
    //     $bucket = Bucket::where('id', $bucket_id)->find();
    //     $cosClient = new CosClient(
    //         array(
    //             'region' => $bucket['area'],
    //             'scheme' => 'https', //协议头部，默认为 http
    //             'credentials' => array(
    //                 'secretId'  => $bucket['apiKey'],
    //                 'secretKey' => $bucket['secret']
    //             )
    //         )
    //     );
    //     //随机目录名+随机文件名.html
    //     //设置文件的content-type
    //     $contentType = 'text/html';
    //     $uuid = Uuid::uuid4()->toString();
    //     $dir = Str::random(10);
    //     $dateStr = date('Ymd');
    //     $dirb = Str::random(10);

    //     $filename = Str::random(10) . '.html';
    //     $cosClient->upload(
    //         $bucket['name'],
    //         $dir . '/' . $dateStr . '/' . $dirb . '/' . $filename,
    //         file_get_contents(root_path() . 'public/rukou.html'),
    //         array(
    //             'Content-Type' => $contentType
    //         )
    //     );
    //     $url = "https://cos.{$bucket['area']}.myqcloud.com/{$bucket['name']}/" . $dir . '/' . $dateStr . '/' . $dirb . '/' . $filename . '?bucket=&ic=' . $code['code'].'&signature='.Str::random(10);

    //     $link = Link::create([
    //         'bucket' => $bucket['name'],
    //         'user_id' => $agent['id'],
    //         'url' => $url,
    //     ]);
    // }
    public function del(): void
    {
        $bce = new Bce([
            'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
            'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
        ]);
        $txbucketName = get_sys_config('tx_name');
        $txarea = get_sys_config('tx_area');
        $cosClient = new CosClient(
            array(
                'region' => $txarea,
                'scheme' => 'https', //协议头部，默认为 http
                'credentials' => array(
                    'secretId'  => get_sys_config('tx_apikey'),
                    'secretKey' => get_sys_config('tx_secret')
                )
            )
        );
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
                if (strpos($v['ldurl'], 'bcebos.com') !== false) {
                    $bce->deleteFile($v['bucket'], str_replace('https://' . $v['bucket'] . '.bj.bcebos.com/', '', $v['ldurl']));
                    $bce->deleteFile($v['bucket'], str_replace('https://' . $v['bucket'] . '.bj.bcebos.com/', '', $v['zzurl']));
                    $bce->deleteBucket($v['bucket']);
                }
                if (strpos($v['ldurl'], 'myqcloud.com') !== false) {
                    $cosClient->deleteObject(
                        array(
                            'Bucket' => $v['bucket'],
                            'Key'    => str_replace('https://cos.' . $txarea . '.myqcloud.com/' . $v['bucket'] . '/', '', $v['ldurl'])
                        )
                    );
                    $cosClient->deleteObject(
                        array(
                            'Bucket' => $v['bucket'],
                            'Key'    => str_replace('https://cos.' . $txarea . '.myqcloud.com/' . $v['bucket'] . '/', '', $v['zzurl'])
                        )
                    );
                }
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
