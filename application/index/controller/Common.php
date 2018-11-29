<?php
namespace app\index\controller;

use think\Controller;
use think\Session;

class Common extends Controller
{
    const TOKEN = '!@#tty280396725';
    /**
     * 基础控制器初始化
     * @author 苏晓信
     */
    public function _initialize()
    {
        //self::__respond(); //请求验证数字签名
        define('MODULE_NAME', request()->module());
        define('CONTROLLER_NAME', request()->controller());
        define('ACTION_NAME', request()->action());

        $this->verify_login();
//        $this ->user_id = 48; // 以后要删除

        $box_is_pjax = $this->request->isPjax();
        $this->assign('box_is_pjax', $box_is_pjax);
    }

    /***
     * 验证登陆状态
     * @return \think\response\Json
     */
    protected function verify_login(){
        // 不需要验证登陆的控制器和方法
        $controller = [
            "index"=>['comp_list', 'comp_info','search_competition'],
            'user'=>['login', 'loginVerify', 'userReg', 'getsmsCode', 'weixin'],
            'uploads'=>['upload_apply'],
        ];
        $req_c = strtolower(CONTROLLER_NAME);
        $req_a = strtolower(ACTION_NAME);
        if(array_key_exists($req_c, $controller) && in_array($req_a, $controller[$req_c])){
            return;
        }
        $user_id = Session::get('user_id');

        if (!$user_id){
            $res = [
                'code' => -1,
                'msg' => '未登录',
                'data' => [],
            ];
            exit(json_encode($res));
        }
		$this->user_id = $user_id;
    }

    /**
     * @Title: _responseResult
     * @Description: todo(统一返回数据格式)
     * @author 苏晓信
     * @date 2017年9月19日
     * @throws
     */
    protected function _responseResult($code, $msg, $data = [])
    {
        $res = [
            'code' => $code,
            'msg' => $msg,
            //'invalidFilter' => $this->invalidFilter,
            'data' => $data,
        ];
        return json($res);
    }

    /**
     * @param $length
     * @return string
     * 用户注册生成的密码盐
     */
    protected function randomkeys($length)
    {
        $key = '';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};
        }
        return $key;
    }

    /**
     * @param $url
     * @param int $timeout
     * @param array $options
     * @return mixed
     * @author:tian
     */
    protected function curl_get($url,$timeout=15,array $options = array())
    {
        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_NOBODY=>false,
            //CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0',
            //CURLOPT_FOLLOWLOCATION=>1,
            CURLOPT_TIMEOUT => $timeout
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /***
     * @param $phone
     * @param $content
     * @return array
     * @author tian 待修改
     */
    public function sendsms($phone,$content)
    {
        $msg='';
        $status=1;
        //短信通道
        $sms=array(
            'username' => 'dianzhi',
            'password' => 'tTdBl7W4i',
            'contents' => $content
        );
        $productid= 1512092;//95533;
        $content=$content;
        $url='http://www.ztsms.cn:8800/sendXSms.do?username='.$sms['username'].'&password='.$sms['password'].'&mobile='.$phone.'&content='. rawurlencode( $content ) .'&productid='.$productid;
        $res=self::curl_get($url);
        $res_arr=explode(',',$res);
        if(count($res_arr)==2&&((int)$res_arr[0]==1))//发送成功
        {
            return array('msg'=>$msg,'status'=>$status,'code'=>$code);
        }else
        {
            $res=strip_tags($res);
            //sys_debug('短信发送失败',"sendsms短信发送失败,返回结果:$res",LEVEL_NOTICE,0);
            $msg='短信发送失败,请稍后重试!';
            $status=0;
            return array('msg'=>$msg,'status'=>$status);
        }

    }

    public function aliyun_sms(){

        ini_set("display_errors", "on");
        require_once ROOT_PATH. 'extend/api_sdk/vendor/autoload.php';

    }

    /***
     * @return \think\response\Json
     * 验证身份
     */
    private function __respond(){
        //验证身份
        $data = request() ->param();
        $timeStamp = $data['time'];
        $randomStr = $data['rand'];
        $signature = $data['sign'];
        if (!empty($timeStamp) && !empty($randomStr) && !empty($signature)){
            $str = $this -> __arithmetic($timeStamp,$randomStr);
            if($str != $signature){
                return self::_responseResult(0,'签名错误');
                exit;
            }
        }
    }

    /**
     * @param $timeStamp 时间戳
     * @param $randomStr 随机字符串
     * @return string 返回签名
     */
    private function __arithmetic($timeStamp,$randomStr){
        $arr['timeStamp'] = $timeStamp;
        $arr['randomStr'] = $randomStr;
        $arr['token'] = self::TOKEN;
        //按照首字母大小写顺序排序
        sort($arr,SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密
        $signature = sha1($str);
        $signature = md5($signature);
        //转换成大写
        $signature = strtoupper($signature);
        return $signature;
    }

    /***
     * @param $value
     * @param $name
     * @return string
     * 科目转换
     */
    protected function getnewValue($value,$name){

        if (in_array($name,array('subject','class','sex','dstatus'))){
            if ($name == 'subject'){
                switch ($value){
                    case 1:
                        return '数学';
                        break;
                    case 2:
                        return '英语';
                        break;
                    case 3:
                        return '语文';
                        break;
                    case 4:
                        return '科技';
                        break;
                    case 5:
                        return '书画';
                        break;
                    default :
                        return $value;
                }
            }elseif ($name == 'class'){
                switch ($value){
                    case 1:
                        return '一年级';
                        break;
                    case 2:
                        return '二年级';
                        break;
                    case 3:
                        return '三年级';
                        break;
                    case 4:
                        return '四年级';
                        break;
                    case 5:
                        return '五年级';
                        break;
                    case 6:
                        return '六年级';
                        break;
                    case 7:
                        return '七年级';
                        break;
                    case 8:
                        return '八年级';
                        break;
                    case 9:
                        return '九年级';
                        break;
                    default :
                        return $value;
                }
            }elseif ($name == 'sex'){
                switch ($value){
                    case 0:
                        return '女';
                        break;
                    case 1:
                        return '男';
                        break;
                    default :
                        return $value;
                }
            }elseif ($name == 'dstatus'){
                switch ($value){
                    case 0:
                        return '待支付';
                        break;
                    case 1:
                        return '待审核';
                        break;
                    case -1:
                        return '审核失败';
                        break;
                    case 2:
                        return '待考试';
                        break;
                    default :
                        return $value;
                }
            }

        }

    }
}