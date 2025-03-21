<?php

namespace app\api\controller;

use app\admin\model\Notice;
use app\admin\model\Order;
use app\admin\model\Theme;
use app\admin\model\user\login\Log;
use app\admin\model\Video;
use app\admin\model\Withdraw;
use app\admin\model\UserMoneyLog;
use think\facade\Cache;
use think\facade\Db;
use Throwable;
use ba\Captcha;
use ba\ClickCaptcha;
use think\facade\Config;
use app\common\facade\Token;
use app\common\controller\Frontend;
use app\api\validate\User as UserValidate;

class User extends Frontend
{
    protected array $noNeedLogin = ['checkIn', 'logout', 'login','getAgentBySecret'];

    protected array $noNeedPermission = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    public function getAgentBySecret()
    {
        $secret = $this->request->param('secret');
        $agent= \app\admin\model\User::where('password',$secret)->find();
        if (!$agent) $this->error('请联系客服');
        if ($agent['status']!=1) $this->error('请联系客服');
        $this->success('',$agent);
    }

    public function login()
    {
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }
        $params = $this->request->post(['username', 'password']);
        $res = $this->auth->login($params['username'], $params['password'], true);
        if (isset($res) && $res === true) {
            //记录日志
            $ip = $this->request->ip();
            $login_time = time();
            $user_id = $this->auth->id;
            $login_log_data = [
                'user_id' => $user_id,
                'ip' => $ip,
                'create_time' => $login_time,
            ];
            Log::create($login_log_data);
            $this->success(__('Login succeeded!'), [
                'userInfo' => $this->auth->getUserInfo(),
            ]);
        } else {
            $msg = $this->auth->getError();
            $msg = $msg ?: __('Check in failed, please try again or contact the website administrator~');
            $this->error($msg);
        }
    }

    public function logout(): void
    {
        if ($this->request->isPost()) {
            $refreshToken = $this->request->post('refreshToken', '');
            if ($refreshToken) Token::delete((string)$refreshToken);
            $this->auth->logout();
            $this->success();
        }
    }

    public function index()
    {
        $agent = $this->auth->getUserInfo();
        $notice = Notice::where('status',1)->order('id', 'desc')->select();
        $total_income =Cache::store('redis')->get('agent:'.$agent['id'].':'.date('Ymd').':total_income');
        $total_order =Cache::store('redis')->get('agent:'.$agent['id'].':'.date('Ymd').':total_order');
        $handler = Cache::store('redis')->handler();
        $today_ip=$handler->sCard('agent:'.$agent['id'].':'.date('Ymd').':ip');
        $conversion_rate = $today_ip==0?0:round($total_order/$today_ip,2)*100;
        $data = [
            'today_income' => $total_income/100,
            'today_orders' => $total_order??0,
            'today_ip' => $today_ip,
            'last_money' => 0,
            'last_orders' => 0,
            'conversion_rate' => $conversion_rate,
            'notices' => $notice,
            'userInfo' => $agent
        ];
        $this->success('请求成功', $data);
    }

    public function getOrderList()
    {
        $agent = $this->auth->getUser();
        $subscribe_type = $this->request->param('subscribe_type');
        $where = [
            'user_id'=>$agent['id'],
            'status'=>1,
        ];
        if ($subscribe_type) {
            $where['subscribe_type'] = $subscribe_type;
        }
        $list = Order::where($where)->with(['video'])->order('id desc')->paginate(20);
        $this->success('ok', $list);
    }

    public function getWithdrawList()
    {
        $agent = $this->auth->getUser();
        $list = Withdraw::where('user_id', $agent['id'])->order('id desc')->paginate(20);
        $this->success('', $list);
    }

    public function withdraw()
    {
        $agent = $this->auth->getUser();
        $payload = $this->request->param();
        $payload['user_id'] = $agent['id'];
        if ($payload['txPassword']!=$agent['txPassword']) $this->error('提现密码错误');
        if ($payload['money'] > $agent['money']) $this->error('余额不足');
        unset($payload['txPassword']);
        Withdraw::create($payload);
        $agent->setDec('money', $payload['money']);
//        UserMoneyLog::create([
//            'user_id' => $agent['id'],
//            'money' => $payload['money'],
//            'memo' => '申请提现',
//        ]);
        $this->success('申请提现成功');
    }

    public function getPriceSetting()
    {
        $agent = $this->auth->getUser();
        $data = [
            'single_price' => $agent['single_price'],
            'day_price' => $agent['day_price'],
            'week_price' => $agent['week_price'],
            'month_price' => $agent['month_price'],
            'free_video'=>Video::query()->cache(3600)->find($agent['free_video']),
        ];
        $this->success('', $data);
    }

    public function savePriceSetting()
    {
        $data = $this->request->post();
        $agent = $this->auth->getUser();
        $agent['single_price'] = $data['single_price'];
        $agent['day_price'] = $data['day_price'];
        $agent['week_price'] = $data['week_price'];
        $agent['month_price'] = $data['month_price'];
        $agent->save();
        $this->success('保存成功');
    }

    public function getThemeSetting()
    {
        $agent = $this->auth->getUser();
        $theme = Theme::query()->select();
        $this->success($agent['theme_id'], $theme);
    }

    public function setThemeSetting()
    {
        $agent = $this->auth->getUser();
        $id = $this->request->post('theme_id', 0);
        $agent->save(['theme_id' => $id]);
        $this->success('ok');
    }

    public function saveFreeVideo()
    {
        $video_id = $this->request->post('video_id', 0);
        $agent = $this->auth->getUser();
        $agent->save(['free_video' => $video_id]);
        $this->success('ok');
    }

    public function getVideoList()
    {
        $list =Video::order('id', 'desc')->paginate(20);
        $this->success('', $list);
    }

    public function getOrderTrend()
    {
        // 获取当前时间
        $currentTime = time();

        // 统计昨天数据（00:00-23:59）
        $yesterdayStart = strtotime('yesterday 00:00:00');
        $yesterdayEnd = strtotime('yesterday 23:59:59');
        $yesterdayData = Order::whereTime('notify_time', 'between', [$yesterdayStart, $yesterdayEnd])
            ->field("FROM_UNIXTIME(notify_time,'%H') as hour, count(*) as count")
            ->group('hour')
            ->select();

        // 统计今天数据（00:00-当前小时）
        $todayStart = strtotime('today 00:00:00');
        $todayData = Order::whereTime('notify_time', 'between', [$todayStart, $currentTime])
            ->field("FROM_UNIXTIME(notify_time,'%H') as hour, count(*) as count")
            ->group('hour')
            ->select();

        // 初始化24小时数组
        $result = [
            'yesterday' => array_fill(0, 24, 0),
            'today' => array_fill(0, 24, 0)
        ];

        // 填充昨天数据
        foreach ($yesterdayData as $item) {
            $result['yesterday'][(int)$item->hour] = $item->count;
        }

        // 填充今天数据
        foreach ($todayData as $item) {
            $result['today'][(int)$item->hour] = $item->count;
        }

        $this->success('', [
            'yesterday' => array_values($result['yesterday']),
            'today' => array_values($result['today'])
        ]);
    }
}