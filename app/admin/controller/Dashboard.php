<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\common\controller\Backend;
use app\admin\model\Order;

class Dashboard extends Backend
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function index(): void
    {
        $this->success('', [
            'today_sell' => Order::where('status', 1)->where('create_time', '>=', strtotime(date('Y-m-d')))->sum('money'),
            'today_order' => Order::where('status', 1)->where('create_time', '>=', strtotime(date('Y-m-d')))->count(),
            'last_sell' => Order::where('status', 1)->where('create_time', '>=', strtotime(date('Y-m-d'),'-1'))->max('money'),
            'last_order' => get_route_remark(),
            'today_agent_money' => User::where('status', 1)->sum('money'),
            'last_agent_money' => get_route_remark(),
            'today_kl' => get_route_remark(),
            'last_kl' => get_route_remark(),
        ]);
    }
}