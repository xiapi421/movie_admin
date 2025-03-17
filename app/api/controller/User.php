<?php

namespace app\api\controller;

use app\admin\model\Bucket;
use app\admin\model\Code;
use app\admin\model\Link;
use app\admin\model\Notice;
use app\admin\model\Order;
use app\admin\model\Theme;
use app\admin\model\user\login\Log;
use app\admin\model\Withdraw;
use think\facade\Cache;
use think\facade\Db;
use app\common\facade\Token;
use app\common\controller\Frontend;
use app\api\validate\User as UserValidate;
use Qcloud\Cos\Client as CosClient;
use think\helper\Str;   
use Ramsey\Uuid\Uuid;
class User extends Frontend
{
    protected array $noNeedLogin = ['checkIn', 'logout', 'login', 'getAgentBySecret', 'info'];

    protected array $noNeedPermission = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    public function info()
    {
        $data = [
            'site_name' => get_sys_config('site_name'),
        ];
        $this->success('请求成功', $data);
    }
    public function getAgentBySecret()
    {
        $secret = $this->request->param('secret');
        $agent = \app\admin\model\User::where('password', $secret)->find();
        if (!$agent) $this->error('请联系客服');
        if ($agent['status'] != 1) $this->error('请联系客服');
        $this->success('', $agent);
    }

    public function login()
    {
        //        if ($this->auth->isLogin()) {
        //            $this->success(__('You have already logged in. There is no need to log in again~'), [
        //                'type' => $this->auth::LOGGED_IN
        //            ], $this->auth::LOGIN_RESPONSE_CODE);
        //        }
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
        $refreshToken = $this->request->post('refreshToken', '');
        if ($refreshToken) Token::delete((string)$refreshToken);
        $this->auth->logout();
        $this->success();
    }

    public function index()
    {
        $agent = $this->auth->getUserInfo();
        $notice = Notice::where('status', 1)->order('id', 'desc')->select();
        $total_income = Cache::store('redis')->get('agent:' . $agent['id'] . ':' . date('Ymd') . ':total_income');
        $total_order = Cache::store('redis')->get('agent:' . $agent['id'] . ':' . date('Ymd') . ':total_order');
        $handler = Cache::store('redis')->handler();
        $today_ip = $handler->sCard('agent:' . $agent['id'] . ':' . date('Ymd') . ':ip');
        $conversion_rate = $today_ip == 0 ? 0 : round($total_order / $today_ip, 2) * 100;
        $data = [
            'today_income' => $total_income / 100,
            'today_orders' => $total_order ?? 0,
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
            'user_id' => $agent['id'],
            'status' => 1,
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
        if ($payload['money'] < 500) $this->error('最小提现金额500');
        if ($payload['txPassword'] != $agent['txPassword']) $this->error('提现密码错误');
        if ($payload['money'] > $agent['money']) $this->error('余额不足');
        unset($payload['txPassword']);
        Withdraw::create($payload);
        $agent->save(['money' => $agent['money'] - $payload['money']]);
        $this->success('申请提现成功');
    }

    public function getPriceSetting()
    {
        $agent = $this->auth->getUser();
        $data = [
            'single_price' => $agent['single_price'],
            'hour_price' => $agent['hour_price'],
            'day_price' => $agent['day_price'],
            'week_price' => $agent['week_price'],
            'month_price' => $agent['month_price'],
            // 'free_video'=>Video::query()->cache(3600)->find($agent['free_video']),
        ];
        $this->success('', $data);
    }

    public function savePriceSetting()
    {
        $data = $this->request->post();
        $agent = $this->auth->getUser();
        $min_single = get_sys_config('min_single');
        $min_day = get_sys_config('min_day');
        $min_hour = get_sys_config('min_hour');

        $max_single = get_sys_config('max_single');
        $max_day = get_sys_config('max_day');
        $max_hour = get_sys_config('max_hour');
        if ($data['single_price'] < $min_single) $this->error('单片最低价格不能低于' . $min_single);
        if ($data['single_price'] > $max_single) $this->error('单片最高价格不能高于' . $max_single);
        if ($data['day_price'] < $min_day) $this->error('单日最低价格不能低于' . $min_day);
        if ($data['day_price'] > $max_day) $this->error('单日最高价格不能高于' . $max_day);
        if ($data['hour_price'] < $min_hour) $this->error('包时最低价格不能低于' . $min_hour);
        if ($data['hour_price'] > $max_hour) $this->error('包时最高价格不能高于' . $max_hour);
        $agent['single_price'] = $data['single_price'];
        $agent['day_price'] = $data['day_price'];
        $agent['hour_price'] = $data['hour_price'];

        // $agent['week_price'] = $data['week_price'];
        // $agent['month_price'] = $data['month_price'];
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
        $id = $this->request->post('theme_id', 1);
        $agent->save(['theme_id' => $id]);
        $this->success('ok');
    }

    public function saveFreeVideo()
    {
        //validate(['video_id'=>'require|url'])->check($this->request->post());
        $video_id = $this->request->post('video_id', '0');
        $agent = $this->auth->getUser();
        $agent->save(['free_video' => $video_id]);
        $this->success('ok');
    }

    //    public function getVideoList()
    //    {
    //        $list =Video::order('id', 'desc')->paginate(20);
    //        $this->success('', $list);
    //    }

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
    //总代相关
    public function agentInfo()
    {
        $agent = $this->auth->getUser();
        $users = \app\admin\model\User::where('up_id', $agent['id'])->select();
        $today_money = 0;
        $today_sell = 0;
        $yesterday_sell = 0;
        foreach ($users as $user) {
            $today_money = $today_money + $user->money;
            $today_sell = $today_sell + $user->today_sell;
            $yesterday_sell = $yesterday_sell + $user->yesterday_sell;
        }
        $data = [
            'agent' => $agent,
            'rate' => $agent['rate'],
            'today_money' => $today_money,
            'today_sell' => $today_sell,
            'yesterday_sell' => $yesterday_sell,
        ];
        $this->success('ok', $data);
    }

    public function getSubUser()
    {
        $agent =  $this->auth->getUser();
        $data = \app\admin\model\User::where('up_id', $agent['id'])->paginate(20);
        $this->success('操作成功', $data);
    }

    public function setSubUserRate()
    {
        $agent = $this->auth->getUser();
        $id = $this->request->post('id', 0);
        $rate = $this->request->post('rate', 50);
        $rate = intval($rate);
        $user =  \app\admin\model\User::find($id);
        if (!$user) $this->error('子代理不存在');
        if ($user['up_id'] != $agent['id']) $this->error('子代理不属于您');
        if ($rate < 50) $this->error('子代理最小分润比例为50%');
        if ($rate > $agent['rate']) $this->error('子代理分润比例不能大于您的分润比例');
        $user->save([
            'rate' => $rate,
        ]);
        $this->success('ok');
    }

    public function getLink()
    {
        $agent = $this->auth->getUser();
        $links = Link::where('user_id', $agent['id'])->order('id', 'desc')->field('id,url')->limit(10)->select();
        $this->success('', ['wechat_links' => $links]);
    }

    public function addLink()
    {
        $agent = $this->auth->getUser();
        $code = Code::where('user_id', $agent['id'])->where('status', 1)->find();
        if(!$code) $this->error('请先创建一个推广码');
        $bucket = Bucket::where('status', '1')->order('id', 'rand')->find();
        $cosClient = new CosClient(
            array(
                'region' => $bucket['area'],
                'scheme' => 'https', //协议头部，默认为 http
                'credentials' => array(
                    'secretId'  => $bucket['apiKey'],
                    'secretKey' => $bucket['secret']
                )
            )
        );
        //随机目录名+随机文件名.html
        //设置文件的content-type
        $contentType = 'text/html';
        $uuid = Uuid::uuid4()->toString();
        $dir = Str::random(10);
        $dateStr = date('Ymd');
        $dirb = Str::random(10);

        $filename = Str::random(10) . '.html';
        $cosClient->upload(
            $bucket['name'],
            $dir . '/'.$dateStr . '/' . $dirb . '/' . $filename,
            file_get_contents(root_path() . 'public/rukou.html'),
            array(
                'Content-Type' => $contentType
            )
        );
        $url = 'https://cos.ap-nanjing.myqcloud.com/'.$bucket['name'].'/' . $dir . '/' . $dateStr . '/' . $dirb . '/' . $filename.'?bucket='.$bucket['bucket'].'&ic='.$code['code'];

        $link = Link::create([
            'bucket' => $bucket['name'],
            'user_id' => $agent['id'],
            'url'=>$url,
        ]);
        $this->success('ok', ['wechat_links' => [$link]]);
    }
}
