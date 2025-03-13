<?php

namespace app\api\controller;

use ba\Bce;
use ba\EpayCore;
use think\facade\Db;
use think\facade\Log;
use think\helper\Str;
use think\facade\Cache;
use think\facade\Route;
use app\admin\model\Pay;
use app\admin\model\Code;
use app\admin\model\Lading;
use app\admin\model\Order;
use app\common\model\User;

use app\common\controller\Frontend;
use app\admin\model\Videos as Video;

class Index extends Frontend
{
    protected array $noNeedLogin = ['*'];
    protected array $noNeedPermission = ['*'];
    protected $redis;

    public function initialize(): void
    {
        $this->redis = Cache::store('redis')->handler();
        parent::initialize();
    }

    //中转
    public function eatmeal()
    {
        $wrongUrl = get_sys_config('error_domain');
        $bucket = $this->request->param('bucket');
        $code = $this->request->param('ic');
        $sign = $this->request->param('sign');
        $lading =Lading::where('bucket', $bucket)->where('status',1)->cache(true)->find();
        if(!$lading) $this->error('error', ['fly' => $wrongUrl], 1002);
        $codeModel = Code::where('code', $code)->cache(3600)->find();
        if ($codeModel['status'] == 0) $this->error('error', ['fly' => $wrongUrl], 1003);
        if ($codeModel['user_id'] < 1) $this->error('error', ['fly' => $wrongUrl], 1004);
        $this->success('success', ['fly' => $lading['remark']."#/home/{$code}"]);
        
    }

    public function index()
    {
        $wrongUrl = get_sys_config('error_domain');
        $ip = $this->request->ip();
        $code = $this->request->param('ic', '0');
        if (empty($code)) $this->error('error', ['fly' => $wrongUrl], 1001);
        $codeModel = Code::where('code', $code)->cache(3600)->find();
        if (!$codeModel) $this->error('error', ['fly' => $wrongUrl], 1002);
        if ($codeModel['status'] == 0) $this->error('error', ['fly' => $wrongUrl], 1003);
        if ($codeModel['user_id'] < 1) $this->error('error', ['fly' => $wrongUrl], 1004);

        $agent = User::where('id', $codeModel['user_id'])->field('id,username,single_price,day_price,week_price,month_price,status,share_status,pay_status,theme_id,free_video')->cache(true)->find();
        if (!$agent) $this->error('error', ['fly' => $wrongUrl], 1002);
        if ($agent['status'] != '1' || $agent['share_status'] != 1) $this->error('error', ['fly' => $wrongUrl], 1003);
        // TODO::判断用户是否黑ip
        $handler = Cache::store('redis')->handler();
        $handler->sadd('agent:' . $agent['id'] . ':' . date('Ymd') . ':ip', ip2long($ip),);

        //        $blackIp = Db::name( 'black_ip' )->where( 'ip', $ip )->find();
        //        if ( $blackIp ) $this->error( 'error', [ 'fly'=>$wrongUrl ], 1004 );
        $payChannel = Db::name('pay')->where('status', 1)
            ->field('id,name,select,weigh,remark,android_status,ios_status,small_status,big_status')
            ->order('weigh desc')->select();
        $paidVideo = Cache::store('redis')->get('single:' . $ip);
        $data = [
            'agent' => $agent,
            'payChannel' => $payChannel,
            'freeVideo' => $agent['free_video'] == '0' ? '0' : ['videoUrl' => $agent['free_video']],
            'paidVideo' => Db::name('videos')->where('id', 'in', $paidVideo)->select()->toArray(),
            'isVip' => Cache::store('redis')->get('term:' . $ip, 0),
            //            'isVip'=>1,
            'random_hot' => get_sys_config('random_hot'),
            'hot_pages' => get_sys_config('hot_pages'),
            'front_name' => get_sys_config('front_name'),
        ];
        $this->success('success', $data);
    }

    public function getPaidVideos()
    {
        $ip = $this->request->ip();
        $paidVideo = Cache::store('redis')->get('single:' . $ip);
        $data = Video::where('id', 'in', $paidVideo)->select()->toArray();
        $this->success('ok', $data);
    }

    public function search()
    {
        $keyword = $this->request->get('keyword');
        $list = Db::name('videos')->where('name', 'like', "%$keyword%")->field('id,name,image,duration')->order('total_purchases')->limit(100)->select();
        $this->success('success', $list);
    }

    public function checkSubscribe()
    {
        $vid = $this->request->param('vid');
        $ip = $this->request->ip();
        $paidVideos = Cache::store('redis')->get('single:' . $ip);
        $isVip = Cache::store('redis')->get('term:' . $ip, 0);
        if ($isVip != 0 || in_array($vid, $paidVideos)) {
            $video = Video::find($vid);
            $this->success('ok', ['video' => $video, 'isVip' => $isVip]);
        }
        $this->error('请购买后观看');
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
        $ip = request()->ip();
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
        $agent = User::where('id', $params['user_id'])->find();
        if (!$agent) $this->error('错误的访问链接');
        if ($agent['status'] != '1' || $agent['pay_status'] != 1) $this->error('无购买权限');
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
                'ip' => $ip,
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
            //调用支付接口，获取支付链接或二维码

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $epay_config = [];
        // $epay_config['apiurl'] = 'http://yy123.15sm.cn/';
        // $epay_config['pid'] = '1308';
        // $epay_config['key'] = '3z0NsO02ygva3BBzuek0KYvWUuvZw2KK';

        $epay_config['apiurl'] = $payChannel['url'];
        $epay_config['pid'] = $payChannel['appid'];
        $epay_config['key'] = $payChannel['secret'];
        $parameter = array(
            'pid' => $epay_config['pid'],
            'type' => $payChannel['select'] == 'wechat' ? 'wxpay' : 'alipay',
            'notify_url' => Route::buildUrl('index/notify',['pay_id'=>$payChannel['id']])->suffix('')->domain(true)->__toString(),
            'return_url' => 'https://www.baidu.com',
            'out_trade_no' => $orderData['order_sn'],
            'name' => $params['subscribe_type'],
            'money'    => $price,
            'clientip' => $ip,
        );
        $epay = new EpayCore($epay_config);
        $html_text = $epay->apiPay($parameter);
        if(isset($html_text['payurl'])){
            Cache::store('redis')->set('order:' . $orderData['order_sn'], 0, 60 * 5);
            $this->success('创建订单成功', [
                'trade_no' => $orderData['order_sn'],
                'payurl' => $html_text['payurl'],
            ]);
        }else{
            $this->error('创建订单失败');
        }
    }

    public function androidCheat($pay)
    {
        $pay = base64_decode($pay);
        // 文件路径
        $file = app()->getRootPath() . 'public/jump.doc';

        // 检查用户代理字符串是否包含 MicroMessenger
        $isWeChat = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;

        if ($isWeChat) {

            // 如果是微信浏览器，则直接下载文件
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else {
            // 如果不是微信浏览器
            // 则使用js重定向
            echo "<script>location.href='{$pay}';</script>";
        }
    }

    public function returnUrl()
    {
        $epay_config = [];
        $epay_config['apiurl'] = 'http://yy123.15sm.cn/';
        $epay_config['pid'] = '1308';
        $epay_config['key'] = '3z0NsO02ygva3BBzuek0KYvWUuvZw2KK';
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyReturn();
        if ($verify_result) {
            //验证成功
            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];
            //支付宝交易号
            $trade_no = $_GET['trade_no'];
            //交易状态
            $trade_status = $_GET['trade_status'];
            //支付方式
            $type = $_GET['type'];

            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            } else {
                echo 'trade_status=' . $_GET['trade_status'];
            }

            return redirect('http://lkljk.cn/front.html?ic=904gsALb');
        } else {
            //验证失败
            echo '<h3>验证失败</h3>';
        }
    }

    public function notify()
    {
        $pay_id = $this->request->get('pay_id');
        $pay = Pay::where('id', $pay_id)->find();
        if (!$pay) $this->error('支付通道不存在');
        $params = $this->request->param();
        Log::write('支付回调参数：' . json_encode($params), 'notice');
        $epay_config = [];
        $epay_config['apiurl'] = $pay['url'];
        $epay_config['pid'] = $pay['appid'];
        $epay_config['key'] = $pay['secret'];
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyNotify();
        if (!$verify_result) {
            Log::write('支付回调签名验证失败', 'error');
            return 'fail';
        }
        $out_trade_no = $_GET['trade_no'];
        $trade_no = $_GET['out_trade_no'];
        $trade_status = $_GET['trade_status'];
        $type = $_GET['type'];
        $money = $_GET['money'];
        if ($_GET['trade_status'] != 'TRADE_SUCCESS') return 'fail';

        Db::startTrans();
        try {
            // 查询订单
            $order = Db::name('order')->where('order_sn', $trade_no)->find();
            if (!$order) {
                Log::write('订单不存在：' . $params['order_sn'], 'error');
                return 'fail';
            }

            // 判断订单状态
            if ($order['status'] != '0') {
                return 'success';
                // 订单已处理，直接返回成功
            }
            $agent = User::find($order['user_id']);
            // 扣量处理 - 每10单扣1单

            $total_orders = Db::name('order')->where('user_id', $order['user_id'])->where('status', 'in', '1,3')->count();
            $is_deducted = false;
            if (($total_orders + 1) % $agent['deducted'] == 0) {
                // 每10单扣1单
                $is_deducted = true;
                // 扣量订单状态设为3
                $status = '3';
            } else {
                $status = '1';
            }

            $rate = $agent['rate'];
            $agent_income = round($order['money'] * $rate / 100, 2);

            // 更新订单状态
            $updateData = [
                'out_order_sn' => $out_trade_no ?? '',
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
                    $agent->save(['money' => $agent['money'] + $agent_income]);
                }

                //总代提成
                if ($agent['up_id'] != 0) {
                    $generalAgent = User::find($agent['up_id']);
                    if ($generalAgent) {
                        $general_income = round($order['money'] * ($generalAgent['rate'] - $rate) / 100, 2);
                        $generalAgent->save(['money' => $generalAgent['money'] + $general_income]);
                    }
                }
            }
            //支付通道统计:
            $pay = Pay::where('id', $order['pay_id'])->find();
            if ($pay) {
                $pay->save([
                    'total_order' => $pay['total_order'] + 1,
                    'total_money' => $pay['total_money'] + $order['money'],
                    'today_order' => $pay['today_order'] + 1,
                    'today_money' => $pay['today_money'] + $order['money']
                ]);
            }
            Db::commit();

            //redis统计
            if ($is_deducted) {
                //统计总扣量
                if (!Cache::store('redis')->has('total:' . date('Ymd') . ':deducted_orders')) {
                    Cache::store('redis')->set('total:' . date('Ymd') . ':deducted_orders', 1, 86400 * 2);
                } else {
                    Cache::store('redis')->inc('total:' . date('Ymd') . ':deducted_orders', 1,);
                }
                if (!Cache::store('redis')->has('total:' . date('Ymd') . ':deducted_amount')) {
                    Cache::store('redis')->set('total:' . date('Ymd') . ':deducted_amount', 1, 86400 * 2);
                } else {
                    Cache::store('redis')->inc('total:' . date('Ymd') . ':deducted_amount', $order['money']);
                }

                Cache::store('redis')->inc('total:deducted_orders', 1);
                Cache::store('redis')->inc('total:deducted_amount', $order['money']);
                //统计代理扣量
                if (!Cache::store('redis')->has('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_orders')) {
                    Cache::store('redis')->set('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_orders', 1, 86400 * 2);
                } else {
                    Cache::store('redis')->inc('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_orders', 1);
                }

                if (!Cache::store('redis')->has('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_amount')) {
                    Cache::store('redis')->set('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_amount', $order['money'], 86400 * 2);
                } else {
                    Cache::store('redis')->inc('agent:' . $agent['id'] . ':' . date('Ymd') . ':deducted_amount', $order['money']);
                }
            } else {
                //代理相关统计
                if (!Cache::store('redis')->has('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_order')) {
                    Cache::store('redis')->set('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_order', 1, 86400 * 2);
                } else {
                    Cache::store('redis')->inc('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_order', 1);
                }

                if (!Cache::store('redis')->has('total:' . date('Ymd') . ':total_income')) {
                    Cache::store('redis')->set('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_income', (int)($agent_income * 100), 86400 * 2);
                } else {
                    Cache::store('redis')->inc('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_income', (int)($agent_income * 100));
                }

                if (!Cache::store('redis')->has('total:' . date('Ymd') . ':total_sell')) {
                    Cache::store('redis')->set('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_sell', $order['money'], 86400 * 2);
                } else {
                    Cache::store('redis')->inc('agent:' . $order['user_id'] . ':' . date('Ymd') . ':total_sell', $order['money']);
                }
            }
            //订阅写入redis
            if ($order['subscribe_type'] == 'single') {
                //                $video = Video::find( $order[ 'video_id' ] );
                Cache::store('redis')->push('single:' . $order['ip'], $order['video_id']);
            }
            if ($order['subscribe_type'] == 'day') {
                Cache::store('redis')->tag('subscribe')->set('term:' . $order['ip'], $order['video_id'], 86400);
            }
            if ($order['subscribe_type'] == 'week') {
                Cache::store('redis')->tag('subscribe')->set('term:' . $order['ip'], $order['video_id'], 604800);
            }
            if ($order['subscribe_type'] == 'month') {
                Cache::store('redis')->tag('subscribe')->set('term:' . $order['ip'], $order['video_id'], 2592000);
            }

            //总后台统计
            Cache::store('redis')->inc('total:' . date('Ymd') . ':total_order', 1);
            Cache::store('redis')->inc('total:' . date('Ymd') . ':total_income', $order['money']);
            //视频购买记录
            Cache::store('redis')->inc('vid:' . $order['video_id'] . ':purchases', 1);
            if (!Cache::store('redis')->has('vid:' . $order['video_id'] . ':' . date('Ymd') . ':purchases')) {
                Cache::store('redis')->set('vid:' . $order['video_id'] . ':' . date('Ymd') . ':purchases', 1, 86400 * 2);
            } else {
                Cache::store('redis')->inc('vid:' . $order['video_id'] . ':' . date('Ymd') . ':purchases', 1);
            }
            //订单redis写入
            Cache::store('redis')->set('order:' . $order['order_sn'], 1, 86400);
            return 'success';
        } catch (\Exception $e) {
            Db::rollback();
            Log::write('支付回调处理异常：' . $e->getMessage(), 'error');
            return 'fail';
        }
    }

    public function checkOrderStatus()
    {
        $tradeno = $this->request->param('tradeno');
        if (empty($tradeno)) $this->error('无此订单');
        $status = Cache::store('redis')->get('order:' . $tradeno, '0');
        $data = [
            'order_sn' => $tradeno,
            'status' => (int)$status,
        ];
        $this->success('查询成功', $data);
    }

    public function tongji()
    {
        //代理相关
        $users = User::where('id', '>', 0)->select();
        foreach ($users as $user) {
            $user->save([
                'lastday_sell' => Cache::store('redis')->get('agent:' . $user['id'] . ':' . date('Ymd') . ':total_sell', 0),
                'lastday_money' => $user['money'],
                'today_order' => 0,
                'today_money' => 0,
            ]);
        }
        Cache::store('redis')->set('total:' . date('Ymd', strtotime('-1 day')) . ':total_agent_money', User::where('status', 1)->sum('money'), 0);

        //支付通道统计
        $pays = Db::name('pay')->where('id', '>', 0)->select();
        foreach ($pays as $pay) {
            $pay->save([
                'lastday_order' => $pay['today_order'],
                'lastday_money' => $pay['lastday_money'],
                'today_order' => 0,
                'today_money' => 0,
            ]);
        }
        $this->success('统计成功');
    }

    public function generateBucket()
    {
        Log::clear();
        $randomStr = 'cs-v10';
        $bce = new Bce([
            'accessKeyId' => 'ALTAKRRYcicQtl9pkL5ys4kJtm',
            'secretAccessKey' => '755757f6d135472e8dd24f5addc9b03b',
        ]);
        $result = $bce->createBucket($randomStr);
        dump($result);
        // $result = $bce->setBucketAcl($randomStr, 'public-read');
        // dump($result['data']);

        // $result = $bce->uploadFile('cs-v9', 'qernv.html', root_path() . 'public/qernv.html');
        // dump($result['data']);

        // 1. 创建存储桶
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
