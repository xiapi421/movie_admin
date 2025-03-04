<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 支付通道
 */
class Pay extends Backend
{
    /**
     * Pay模型对象
     * @var object
     * @phpstan-var \app\admin\model\Pay
     */
    protected object $model;

    protected string|array $defaultSortField = 'weigh,desc';

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Pay();
    }

    public function clear()
    {
        $id = $this->request->param('id');
        $res = $this->model->where('id', $id)->update(['total_money' => 0.00, 'total_order' => 0, 'today_money' => 0.00,'today_order'=>0,'lastday_money'=>0.00,'lastday_order'=>0]);
        $this->success('清空成功');
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}