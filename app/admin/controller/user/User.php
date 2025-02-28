<?php

namespace app\admin\controller\user;

use app\admin\model\user\login\Log;
use app\common\controller\Backend;
use app\common\facade\Token;
use app\common\library\Auth;
use ba\Random;
use think\facade\Db;
use think\facade\Event;

/**
 * 会员管理
 */
class User extends Backend
{
    /**
     * User模型对象
     * @var object
     * @phpstan-var \app\admin\model\User
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'update_time', 'create_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\User();
    }

    public function add(): void
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }
            $time = time();
            $salt = Random::build('alnum', 16);
            $data = [
                'password'        => encrypt_password($params['password'], $salt),
                'group_id'        => 1,
                'salt'            => $salt,
                'single_price'    => get_sys_config('min_single'),
                'day_price'    => get_sys_config('min_day'),
                'week_price'    => get_sys_config('min_week'),
                'month_price'    => get_sys_config('min_month'),
                'invite_code'     => Random::build('alnum', 8),
            ];
            $data = array_merge($params, $data);
            Db::startTrans();
            try {
                $this->model->create($data);
                Db::commit();
            } catch (Throwable $e) {
                $this->setError($e->getMessage());
                Db::rollback();
                $this->error(__('Parameter error'));
            }
            $this->success('ok');
        }

        $this->error(__('Parameter error'));
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
            ->order($order)
            ->paginate($limit);
        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    public function getLoginLog()
    {
        $user_id= $this->request->param('user_id');
        $data =Log::where('user_id',$user_id)->order('id desc')->limit(10)->select();
        $this->success('ok', $data);
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}