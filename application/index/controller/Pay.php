<?php
namespace app\index\controller;

use think\Cache;

class Pay extends Common
{

    public function _initialize(){
        parent::_initialize();
    }

    /***
     *测试
     */
    public function index(){

        require_once(ROOT_PATH."/extend/alipay/AopSdk.php");
        //构造参数
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';
        $aop->appId = '2016080800192538';
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEA+hKBEb2Wiz35zvv5r2AXVwcpFhhGbu1TutVBULY3DoDlmFomwqtjoPVoLZd51cbKEyustgjm6eJtmc+yHSptutZ5BneGQiPiVaKcw48ZKI7JEzmYVH4KcAZyE0KtRAJXUURv3hu+/UmYvcp3JykDWSz4rXLTRxFnmJIny+4iALxmqm5akTrpmQgThv4Zi4eC9VFJ8xRheWKQxTjHBasYaJplxFiAjkR8ERt8PO84SV+cl3JBr2B1wQcVXc+w7sftGiju1S0lyfaHpBe1FmTwWUB2Lb+r89eg72//pnlhSGZMJiaV+lMcu2FIIBwm441SBt5KeK/AtJXkCL/7dq6YuwIDAQABAoIBAQDEtoOjUfC3bbQxdxMNOtiBVBek0sms/rGruY9cj0m19f0loFz3K1z+w60EmDB10p8o/2Un+M60UrKGmNPyj2qr24Ruat1I7/NeC8GnL8zJk7BmmBrU9CT/xII91mh3pCPNwLkDDe5qTleBjF+4hVGl93NS9Y1vTSih4u69Q4Cp8/v0ecK75UMasbFnjWVY/EnguDo2NQqUO3EJ6XUEJjt8iQcoNrDolJtnytswvvs7quq+s5QKwVZJH4md2GQc4riKWU3nd1Y+j9OAQe+gHv4RvCGKxdvHuX0xZ2mJk7sKXid+NxfmIQ4bIiFWD+xGaILccYMFy9N43dtleZhe7IcxAoGBAP+HV4MVy9+ZpTvRNDf9aETaH0rdIty4/xl4JQZkgmAua9IrBJz7l3InlB3mQ0GSpt1Fisu9oCYJFBS92/w01OjADiprD5eO5X79wUb3Bg5ZfIF0fJPsEFgLAt2tx8TcrK/CMPYOvOkS6b3OOKpHepK61NKVYKnVo/sNpwyV9n5ZAoGBAPqIlfvtd7HC3AMAG3G5alXm8exsFpbLtdtLft6KT3MOKvaH2A/kG1+Ck8hy754NQmBbSGne9DQTqvIO7olsbLNvPBJE1hW1CkzGT1wIL/hlYkNbZbFLt/xSHK7CxjIthJjbpDIOfcYm8Zs7eMz1gz2FoMhhpMg9UpeZq04CYzUzAoGAQMIrEoSWm39T2doGEt58614gKhfq+udDd0/0ii80v21kU+olDCfS1NJk/kLZ7qdc9JzoNQRErv8EANGxC7TT9Hyf2m4xkGZdkRZ8QiDefwp8vE4qOE7OQZHg0w90nlaSySQ8xk8r3yG07S5zO+xLix4gS5Ih4kjLexeVq2HiC1kCgYAy9SkcjtZzr6C0c4chgIciZdD7N5j4nwKkUhzCAvvZ+R2/+y11Pf5bVOHeOZKHYUcI9kgqUJD3LrDsfyEBjq4laRCc3qd0ztgDeaqWm4u2SFjOPn7WqwIHLmRrH27UsfFwbexdyhjG/xDRdC1D8wP4tX9YgpPTrrVn5He1bELlhQKBgD3W+a3BklEoFwfuPtaDejoyvOk2dOQQGyxK+CLGACvZ8do4zvxaeFa3bmnIY6xD+MLdmpBysTqvAV7MmUAMSZiLAprl0GbJLTMQt5nBYzAM5X+4PSW/TNL0b5bn1TnsC8uP4uvH4hKF7T7vos8kzpCXXkcwq7il+Q9Q5SUeQ63j';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset= 'utf-8';
        $aop->format='json';
        $request = new \AlipayTradePagePayRequest ();
        $request->setReturnUrl('https://www.baidu.com');  //同步跳转
        $request->setNotifyUrl('http://localhost/index.php/index/pay/aa');  //异步通知
        $request->setBizContent('{"product_code":"FAST_INSTANT_TRADE_PAY","out_trade_no":"20150320010101005","subject":"Iphone6 16G","total_amount":"88.88","body":"Iphone6 16G"}');


        //请求
        $result = $aop->pageExecute ($request);

        //输出
        echo $result;
    }

    public function aa(){
        Cache::set('sss',5555555,3000);
        die();
    }

    public function bb(){
        require_once ROOT_PATH.'/extend/alipay/index.php';
    }


}
