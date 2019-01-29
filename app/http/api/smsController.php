<?php
namespace app\http\api;

use Small\http\HttpController;
use Small\lib\auth\User;
use Small\lib\sms\SmsCn;
use Small\lib\util\Str;

/**
 * Class smsController
 * @package app\http\api
 */
class smsController extends HttpController{

    /**
     * 短信发送
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        $type = $this->getPostData('trim:type', null, "缺少参数");
        $list = [
            "register",
            "update_sp",
            "find_pass"
        ];
        if(in_array($type, $list)){
            $phone = null;
            switch ($type){
                case "update_sp" :
                    //需要登录
                    if(!User::isLogin()){
                        $this->response(-1, "未登录");
                    }
                    $phone = User::$staticUserInfo['phone'] ?? null;
                    break;
                default :
                    $phone = $this->getPostData('phone', null, '缺少手机号码');
                    break;
            }
            if(Str::isPhoneNumber($phone)){
                //可发送
                if(SmsCn::sendMessage($phone, $type)){
                    $this->response(0, '已发送');
                }else{
                    $this->response("短信发送失败");
                }
            }else{
                $this->response("手机号码格式错误");
            }
        }else{
            $this->response("参数错误");
        }
    }

}