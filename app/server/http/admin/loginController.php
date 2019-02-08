<?php
namespace app\server\http\admin;

use Small\annotation\parser\Method;
use Small\annotation\parser\View;
use Small\lib\auth\User;
use Small\server\http\RequestController;

/**
 * Class loginController
 * @package app\server\http\admin
 */
class loginController extends RequestController{

    /**
     * 后台登录
     * @View("login2")
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        //return parent::index($args); // TODO: Change the autogenerated stub
    }

    /**
     * 登录提交入口
     * @Method("AJAX_POST")
     */
    public function submit(){
        $username = $this->getPostData('trim:username', null, lang("framework.login.101"));
        $password = $this->getPostData('password', null, lang("framework.login.102"));
        $verify_code = $this->getPostData('trim:verify_code', null, lang("framework.login.103"));
        //判断验证码是否正确
        if(!matchVerifyCode($verify_code)){
            $this->response(lang("framework.login.104"));
        }
        resetVerifyCode();
        $u = new User(false);
        if($u->login($username, $password)){
            $info = $u->userInfo;
            if($info['group_type']=='admin'){
                //返回TOKEN和跳转地址
                $this->response(0, [
                    "token"     => $info['token'],
                    "callback_url" => url("/admin")
                ]);
            }else{
                $u->logout();
                $this->response(lang("framework.auth.105"));
            }
        }else{
            $this->response($u->errorInfo);
        }
    }
}