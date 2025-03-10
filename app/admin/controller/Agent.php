<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use ba\Random;
use think\facade\Db;

/**
 * 总代管理
 */
class Agent extends Backend
{
    /**
     * Agent模型对象
     * @var object
     * @phpstan-var \app\admin\model\Agent
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\User();
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
            ->where('group_id',2)
            ->order($order)
            ->paginate($limit);
        $list = $res->items();
        $subUsersIds = [];
        $yesterdayBegin = strtotime('yesterday midnight');
        $yesterdayEnd = strtotime('today midnight');
        foreach ($list as &$item) {
            $subUsersIds = $this->model->where('up_id', $item['id'])->column('id');
            $item['subUsersId'] = $subUsersIds;
            $item['agent_today_sell'] = Db::name('order')->where('user_id','in',$subUsersIds)
                ->where('notify_time','>=',$yesterdayBegin)->where('notify_time','<',$yesterdayEnd)->where('status','in',[1,3])->sum('money');
            $item['agent_yesterday_sell'] = Db::name('order')->where('user_id','in',$subUsersIds)
                ->where('notify_time','>=',$yesterdayBegin)->where('notify_time','<',$yesterdayEnd)->where('status','in',[1,3])->sum('money');
            $item['all_sell']= Db::name('order')->where('user_id','in',$subUsersIds)->where('status','in',[1,3])->sum('money');
        }
        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 添加
     */
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
                'group_id'        => 2,
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

    public function edit(): void
    {
        $pk  = $this->model->getPk();
        $id  = $this->request->param($pk);
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds && !in_array($row[$this->dataLimitField], $dataLimitAdminIds)) {
            $this->error(__('You have no permission'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data   = $this->excludeFields($data);
            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    if (class_exists($validate)) {
                        $validate = new $validate();
                        if ($this->modelSceneValidate) $validate->scene('edit');
                        $data[$pk] = $row[$pk];
                        $validate->check($data);
                    }
                }
                $salt   = Random::build('alnum', 16);
                $data['password'] = encrypt_password($data['password'], $salt);
                $data['salt']     = $salt;
//                $data['password']= encrypt_password($row['password'], $row['salt']);
                $result = $row->save($data);
                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        $this->success('', [
            'row' => $row
        ]);
    }
    public function addSubUser()
    {
        $username= $this->request->param('username');
        $agent_id= $this->request->param('agent_id');
        $user = $this->model->where('username', $username)->where('group_id','1')->find();
        $yl = $user['up_id'];
        if (!$user) $this->error('子代理不存在');
        $agent = $this->model->where('id', $agent_id)->where('group_id','2')->find();
        if (!$agent) $this->error('总代理不存在');
        $user->save(['up_id' => $agent_id,'up_username'=>$agent['username']]);
        $this->success("子代理上级id由{$yl}->{$agent_id}");
    }

    public function getSubUser()
    {
        $agent_id = $this->request->param('agent_id');
        $users = $this->model->where('up_id',$agent_id)->select();
        $this->success('操作成功',$users);
    }
    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}