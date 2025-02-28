<?php

namespace app\api\controller;

use app\admin\model\Notice;
use app\admin\model\Order;
use app\admin\model\Theme;
use app\admin\model\user\login\Log;
use app\admin\model\Withdraw;
use app\common\model\UserMoneyLog;
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
    protected array $noNeedLogin = ['checkIn', 'logout', 'login'];

    protected array $noNeedPermission = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 会员签入(登录和注册)
     * @throws Throwable
     */
    public function checkIn(): void
    {
        $openMemberCenter = Config::get('buildadmin.open_member_center');
        if (!$openMemberCenter) {
            $this->error(__('Member center disabled'));
        }

        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }

        $userLoginCaptchaSwitch = Config::get('buildadmin.user_login_captcha');

        if ($this->request->isPost()) {
            $params = $this->request->post(['tab', 'email', 'mobile', 'username', 'password', 'keep', 'captcha', 'captchaId', 'captchaInfo', 'registerType']);

            // 提前检查 tab ，然后将以 tab 值作为数据验证场景
            if (!in_array($params['tab'] ?? '', ['login', 'register'])) {
                $this->error(__('Unknown operation'));
            }

            $validate = new UserValidate();
            try {
                $validate->scene($params['tab'])->check($params);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            if ($params['tab'] == 'login') {
                if ($userLoginCaptchaSwitch) {
                    $captchaObj = new ClickCaptcha();
                    if (!$captchaObj->check($params['captchaId'], $params['captchaInfo'])) {
                        $this->error(__('Captcha error'));
                    }
                }
                $res = $this->auth->login($params['username'], $params['password'], !empty($params['keep']));
            } elseif ($params['tab'] == 'register') {
                $captchaObj = new Captcha();
                if (!$captchaObj->check($params['captcha'], $params[$params['registerType']] . 'user_register')) {
                    $this->error(__('Please enter the correct verification code'));
                }
                $res = $this->auth->register($params['username'], $params['password'], $params['mobile'], $params['email']);
            }

            if (isset($res) && $res === true) {
                $this->success(__('Login succeeded!'), [
                    'userInfo' => $this->auth->getUserInfo(),
                    'routePath' => '/user'
                ]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ?: __('Check in failed, please try again or contact the website administrator~');
                $this->error($msg);
            }
        }

        $this->success('', [
            'userLoginCaptchaSwitch' => $userLoginCaptchaSwitch,
            'accountVerificationType' => get_account_verification_type()
        ]);
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
        $agent = $this->auth->getUserinfo();
        $notice = Notice::order('id', 'desc')->cache(true)->select();
        $total_income =Cache::store('redis')->get('agent:'.$agent['id'].':'.date('Ymd').':total_income');
        $total_order =Cache::store('redis')->get('agent:'.$agent['id'].':'.date('Ymd').':total_order');
        $handler = Cache::store('redis')->handler();
        $today_ip=$handler->sCard('agent:'.$agent['id'].':'.date('Ymd').':ip');


        $data = [
            'today_income' => $total_income/100,
            'today_orders' => $total_order,
            'today_ip' => $today_ip,
            'conversion_rate' => round($total_order/$today_ip,2)*100,
            'notices' => $notice,
            'userInfo' => $agent
        ];
        $this->success('请求成功', $data);
    }

    public function getOrderList()
    {
        $agent = $this->auth->getUser();
        $list = Order::where('user_id', $agent['id'])->where('status','1')->with(['video'])->order('id desc')->paginate(20);
        $this->success('ok', $list);
    }

    public function getWithdrawList()
    {
        $agent = $this->auth->getUser();
        $list = Withdraw::where('user_id', $agent['id'])->where('status',1)->order('id desc')->paginate(20);
        $this->success('', $list);
    }

    public function withdraw()
    {
        $agent = $this->auth->getUser();
        $payload = $this->request->param();
        //验证...
        if ($payload['money'] > $agent['money']) $this->error('余额不足');
        Withdraw::create($payload);
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
        $id = $this->request->post('id', 0);
        $agent->save(['theme_id' => $id]);
        $this->success('ok');
    }
}