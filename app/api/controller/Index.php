<?php

namespace app\api\controller;

use app\admin\model\Order;
use app\admin\model\UserMoneyLog;
use ba\Tree;
use think\facade\Cache;
use think\facade\Log;
use Throwable;
use think\facade\Db;
use think\facade\Config;
use app\common\controller\Frontend;
use app\common\library\token\TokenExpirationException;
use app\common\model\User;

class Index extends Frontend
{
    protected array $noNeedLogin = ['*'];
    protected array $noNeedPermission = ['*'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 前台和会员中心的初始化请求
     * @throws Throwable
     */
    public function indexb(): void
    {
        $menus = [];
        if ($this->auth->isLogin()) {
            $rules = [];
            $userMenus = $this->auth->getMenus();

            // 首页加载的规则，验权，但过滤掉会员中心菜单
            foreach ($userMenus as $item) {
                if ($item['type'] == 'menu_dir') {
                    $menus[] = $item;
                } elseif ($item['type'] != 'menu') {
                    $rules[] = $item;
                }
            }
            $rules = array_values($rules);
        } else {
            // 若是从前台会员中心内发出的请求，要求必须登录，否则会员中心异常
            $requiredLogin = $this->request->get('requiredLogin/b', false);
            if ($requiredLogin) {

                // 触发可能的 token 过期异常
                try {
                    $token = get_auth_token(['ba', 'user', 'token']);
                    $this->auth->init($token);
                } catch (TokenExpirationException) {
                    $this->error(__('Token expiration'), [], 409);
                }

                $this->error(__('Please login first'), [
                    'type' => $this->auth::NEED_LOGIN
                ], $this->auth::LOGIN_RESPONSE_CODE);
            }

            $rules = Db::name('user_rule')
                ->where('status', '1')
                ->where('no_login_valid', 1)
                ->where('type', 'in', ['route', 'nav', 'button'])
                ->order('weigh', 'desc')
                ->select()
                ->toArray();
            $rules = Tree::instance()->assembleChild($rules);
        }

        $this->success('', [
            'site' => [
                'siteName' => get_sys_config('site_name'),
                'recordNumber' => get_sys_config('record_number'),
                'version' => get_sys_config('version'),
                'cdnUrl' => full_url(),
                'upload' => keys_to_camel_case(get_upload_config(), ['max_size', 'save_name', 'allowed_suffixes', 'allowed_mime_types']),
            ],
            'openMemberCenter' => Config::get('buildadmin.open_member_center'),
            'userInfo' => $this->auth->getUserInfo(),
            'rules' => $rules,
            'menus' => $menus,
        ]);
    }

    public function index()
    {
        $wrongUrl = get_sys_config('error_domain');
        $ip = $this->request->ip();
        $code = $this->request->param('ic', '0');
        if (empty($code)) $this->error('error', ['fly' => $wrongUrl], 1001);
        $agent = User::where('invite_code', $code)->field('id,username,single_price,day_price,week_price,month_price,status,share_status,pay_status,theme_id')->find();
        if (!$agent) $this->error('error', ['fly' => $wrongUrl], 1002);
        if ($agent['status'] != '1' || $agent['share_status'] != 1) $this->error('error', ['fly' => $wrongUrl], 1003);
        // TODO::判断用户是否黑ip
        $handler = Cache::store('redis')->handler();
        $handler->sadd('agent:'.$agent['id'].':'.date('Ymd').':ip', ip2long($ip));

//        $blackIp = Db::name('black_ip')->where('ip', $ip)->find();
//        if ($blackIp) $this->error('error',['fly'=>$wrongUrl],1004);
        $payChannel = Db::name('pay')->order('weigh desc')->select();
        $data = [
            'agent' => $agent,
            'payChannel' => $payChannel,
        ];
        $this->success('success', $data);
    }

    public function updateVideoClicks()
    {
        $id = $this->request->get('vid');
        if (Cache::store('redis')->has("vid:$id:click")) {
            Cache::store('redis')->inc("vid:$id:click", 1);
        } else {
            Cache::store('redis')->set("vid:$id:click", 1, 0);
        }
    }

    public function updateVideoViews()
    {
        $ids = $this->request->get('vids');
        $arr = explode(',', $ids);
        foreach ($arr as $id) {
            if (Cache::store('redis')->has("vid:$id:view")) {
                Cache::store('redis')->inc("vid:$id:view", 1);
            } else {
                Cache::store('redis')->set("vid:$id:view", 1, 0);
            }
        }
        $this->success('', $ids);
    }

    public function createOrder()
    {

        $params = $this->request->param();
        // 参数验证
        $validate = validate([
            'user_id' => 'require|number|gt:0',
            'video_id' => 'require|number|gt:0',
            'subscribe_type' => 'require|in:single,day,week,month',
            'pay_id' => 'require|number|gt:0',
        ]);

        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }
        $agent = User::where('id',$params['user_id'])->find();
        if (!$agent) $this->error('错误的访问链接');
        if ($agent['status']!= '1' || $agent['pay_status']!= 1) $this->error('无购买权限');
        $price = 0;
        switch ($params['subscribe_type']) {
            case 'single':
                $price = $agent['single_price'];
                break;
            case 'day':
                $price = $agent['day_price'];
                break;
            case 'week':
                $price = $agent['week_price'];
                break;
            case 'month':
                $price = $agent['month_price'];
                break;
            default:
                $this->error('错误的订阅类型');
                break;
        }
        $payChannel = Db::name('pay')->where('id', $params['pay_id'])->find();
        if ($payChannel['status'] != 1) $this->error('支付通道已关闭');

        Db::startTrans();
        try {
            $orderData = [
                'order_sn' => $this->generateOrderSn(),
                'ip' => request()->ip(),
                'user_id' => $params['user_id'],
                'video_id' => $params['video_id'],
                'subscribe_type' => $params['subscribe_type'],
                'pay_id' => $params['pay_id'],
                'money' => $price,
                'create_time' => time(),
                'update_time' => time(),
                'status' => '0'
            ];
            // 创建订单
            $orderId = Db::name('order')->insertGetId($orderData);
            // TODO: 调用支付接口，获取支付链接或二维码
            // 这里需要根据实际的支付渠道来实现
            $payInfo = [
                'order_sn' => $orderData['order_sn'],
                'money' => $price, // 这里需要根据实际订阅类型计算金额
                'pay_url' => 'https://www.baidu.com' // 这里需要对接实际的支付接口
            ];
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('创建订单成功', $payInfo);
    }

    public function notify()
    {
        $params = $this->request->param();
        Log::write('支付回调参数：'.json_encode($params), 'notice');

        Db::startTrans();
        try {
            // 验证签名等安全校验
            // TODO: 根据实际支付渠道实现签名验证

            // 查询订单
            $order = Db::name('order')->where('order_sn', $params['order_sn'])->find();
            if (!$order) {
                Log::write('订单不存在：'.$params['order_sn'], 'error');
                return 'fail';
            }

            // 判断订单状态
            if ($order['status'] != '0') {
                return 'success'; // 订单已处理，直接返回成功
            }

            // 扣量处理 - 每10单扣1单
            $total_orders = Db::name('order')->where('user_id', $order['user_id'])->where('status','in','1,3')->count();
            $is_deducted = false;
            if (($total_orders + 1) % 10 == 0) { // 每10单扣1单
                $is_deducted = true;
                // 扣量订单状态设为3
                $status = '3';
                // 记录扣量统计
                Cache::store('redis')->inc('total:'.date('Ymd').':deducted_orders', 1);
                Cache::store('redis')->inc('total:'.date('Ymd').':deducted_amount', $order['money']);
                Cache::store('redis')->inc('total:deducted_orders', 1);
                Cache::store('redis')->inc('total:deducted_amount', $order['money']);
            } else {
                $status = '1';
            }

            $agent = User::find($order['user_id']);
            $rate = get_sys_config('rate');
            $agent_income = round($order['money']*$rate/100, 2);

            // 更新订单状态
            $updateData = [
                'out_order_sn' => $params['transaction_id'] ?? '',
                'status'       => $status,
                'agent_money' => $agent_income,
                'notify_time'   => time(),
                'update_time'   => time()
            ];

            Db::name('order')->where('id', $order['id'])->update($updateData);

            // 非扣量订单才处理代理分成和统计
            if (!$is_deducted) {
                // 处理代理分成等业务逻辑
                if ($agent) {
                    UserMoneyLog::create([
                        'user_id' => $agent['id'],
                        'money' => $agent_income,
                        'memo'=> '订单收入 '.$order['order_sn'],
                    ]);
                }

                
                //代理相关统计
                Cache::store('redis')->inc('agent:'.$order['user_id'].':'.date('Ymd').':total_order', 1);
                Cache::store('redis')->inc('agent:'.$order['user_id'].':'.date('Ymd').':total_income', (int)($agent_income*100));
                Cache::store('redis')->inc('agent:'.$order['user_id'].':'.date('Ymd').':total_sell', $order['money']);
            }else{
                //统计代理扣量
                Cache::store('redis')->inc('agent:'.$agent['id'].':'.date('Ymd').':deducted_orders', 1);
                Cache::store('redis')->inc('agent:'.$agent['id'].':'.date('Ymd').':deducted_amount', $order['money']);
            }
            //订阅写入redis
            if ($order['subscribe_type'] == 'single') {
                Cache::store('redis')->tag('subscribe')->set("single:".$order['ip'], $order['video_id'], 0);
            }
            if ($order['subscribe_type'] == 'day') {
                Cache::store('redis')->tag('subscribe')->set("day:".$order['ip'], true, 86400);
            }
            if ($order['subscribe_type'] == 'week') {
                Cache::store('redis')->tag('subscribe')->set("week:".$order['ip'], true, 604800);
            }
            if ($order['subscribe_type'] == 'month') {
                Cache::store('redis')->tag('subscribe')->set("month:".$order['ip'], true, 2592000);
            }

            //总后台统计
            Cache::store('redis')->inc('total:'.date('Ymd').':total_order', 1);
            Cache::store('redis')->inc('total:'.date('Ymd').':total_income', $order['money']);


            Db::commit();
            return 'success';

        } catch (\Exception $e) {
            Db::rollback();
            Log::write('支付回调处理异常：'.$e->getMessage(), 'error');
            return 'fail';
        }
    }

    public function tongji()
    {
        $users = User::where('id', '>', 0)->select();
        foreach ($users as $user) {
            $user->save([
                'lastday_sell'=>Cache::store('redis')->get('agent:'.$user['id'].':'.date('Ymd').':total_sell',0),
                'lastday_money'=>$user['money'],
            ]);
        }
        Cache::store('redis')->set('total:'.date('Ymd',strtotime('-1 day')).':total_agent_money', User::where('status', 1)->sum('money'), 0);
        $this->success('统计成功');
    }



    protected function generateOrderSn()
    {
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;

        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }

        $order_sn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $order_sn;
    }
    
    
}