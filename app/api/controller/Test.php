<?php
namespace app\api\controller;


use app\BaseController;

class Test extends BaseController{

    public function androidCheat()
    {


        // 文件路径
        $file = 'jump.doc';

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
            echo '<script>location.href="https://ulink.alipay.com/?scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FsaId%3D10000007%26clientVersion%3D3.7.0.0718%26qrcode%3Dhttps%253A%252F%252Frender.alipay.com%252Fp%252Fc%252Falipay-red-qrcode%252Fshared.html%253Fchannel%253Dsearch_pwd%2526shareId%253D2088602294611742%2526token%253D196139496tmg2vcinfrii8chMb%2526campStr%253DkPPFvOxaCL3f85TiKss2wsBZgIjulHjG%2526sign%253DqsiVOoa7TuphryWxyBdONXsMTnE3jiIBvWeUs3yV1sw%253D%2526chInfo%253DDingtalk%2526c_stype%253Dsearch_pwd";</script>';
        }
    }
}