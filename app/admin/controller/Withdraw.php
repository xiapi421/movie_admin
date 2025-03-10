<?php

namespace app\admin\controller;

use app\admin\model\UserMoneyLog;
use Throwable;
use app\common\controller\Backend;
use app\admin\model\Withdraw as WithdrawModel;
/**
 * 提现管理
 */
class Withdraw extends Backend
{
    /**
     * Withdraw模型对象
     * @var object
     * @phpstan-var \app\admin\model\Withdraw
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected array $withJoinTable = ['user'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Withdraw();
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
        $res->visible(['user' => ['username','group_id']]);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */

    public function handle()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $withdraw = $this->model->find($id);
        if (!$withdraw) $this->error('提现记录不存在');
        WithdrawModel::where('id', $id)->update(['status' => $status,'handle_time' => time()]);
        if ($status==2){
            UserMoneyLog::create(['user_id' => $withdraw['user_id'], 'money' => $withdraw['money'], 'memo' => '已驳回']);
        }
        $this->success('操作成功');
    }

    public function getWithdrawCount()
    {
        $waitAmount = $this->model->where('status', 0)->sum('money');

        // 今日已确认金额
        $todayStart = strtotime('today');
        $todayEnd = strtotime('tomorrow') - 1;
        $todayAmount = $this->model->where('status', 1)
            ->where('handle_time', 'between', [$todayStart, $todayEnd])
            ->sum('money');

        // 昨日已确认金额
        $yesterdayStart = strtotime('yesterday');
        $yesterdayEnd = strtotime('today') - 1;
        $yesterdayAmount = $this->model->where('status', 1)
            ->where('handle_time', 'between', [$yesterdayStart, $yesterdayEnd])
            ->sum('money');
        $this->success('请求成功', [
           'waitAmount' => $waitAmount,
           'todayAmount' => $todayAmount,
           'yesterdayAmount' => $yesterdayAmount,
        ]);
    }
}