<?php
namespace app\http\api;

use Small\http\HttpController;
use Small\lib\auth\User;
use Small\lib\sms\SMS;
use Small\lib\util\Str;
use Small\model\models\UserModel;

/**
 * 找回密码
 * Class findpassController
 * @package app\http\api
 */
class findpassController extends HttpController{

    /**
     * 找回密码
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        $phone = $this->getPOSTData("phone", null, "请填写手机号码");
        if(!Str::isPhoneNumber($phone)){
            $this->response("手机号码格式错误");
        }
        $phone_code = $this->getPOSTData("phone_code", null, "请填写手机验证码");
        if(strlen($phone_code)!=6){
            $this->response("手机验证码错误");
        }
        $password = $this->getPOSTData("password", null, "请填写登录密码");
        if(strlen($password)<6 || strlen($password)>16){
            $this->response("密码长度必须6~16位长度");
        }
        if(!SMS::checkSMS($phone, $phone_code, "find_pass")){
            $this->response("手机验证码无效或已过期");
        }
        //验证手机号码是否正确
        $um = new UserModel();
        $temp = $um->mainQuery("u")
            ->where("u.phone", $phone)
            ->first();
        if(!empty($temp)){
            //更改密码
            $u = new User(false);
            if($u->updatePassword($temp['uid'], $password)){
                SMS::checkedSMS($phone, 'find_pass');
                $this->response(0, "密码已重置");
            }else{
                $this->response("重置密码失败");
            }
        }else{
            $this->response("没有这个账号");
        }
    }
}