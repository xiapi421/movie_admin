<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\common\controller\Backend;
use app\admin\model\Order;
use think\facade\Cache;

class Dashboard extends Backend
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function index(): void
    {
        $today_kl = Cache::store('redis')->get('total:'.date('Ymd').':deducted_orders', 0);
        $today_kl_amount = Cache::store('redis')->get('total:'.date('Ymd').':deducted_amount', 0);
        $today_sell = Cache::store('redis')->get('total:'.date('Ymd').':total_income', 0);
        $today_order = Cache::store('redis')->get('total:'.date('Ymd').':total_order', 0);
        $last_sell = Cache::store('redis')->get('total:'.date('Ymd',strtotime('-1 day')).':total_income', 0);
        $last_order = Cache::store('redis')->get('total:'.date('Ymd',strtotime('-1 day')).':total_order', 0);
        $last_agent_money = Cache::store('redis')->get('total:'.date('Ymd',strtotime('-1 day')).':total_agent_money', 0);
        $this->success('', [
            'today_sell' => $today_sell,
            'today_order' => $today_order,
            'last_sell' => $last_sell,
            'last_order' => $last_order,
            'today_agent_money' => User::where('status', 1)->sum('money'),
            'last_agent_money' => $last_agent_money,
            'today_kl' => $today_kl,
            'last_kl' => round($today_kl_amount/100,2),
        ]);
    }
}