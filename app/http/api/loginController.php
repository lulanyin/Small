<?php
namespace app\http\api;

use Small\http\HttpController;
use Small\lib\auth\User;
use Small\lib\util\Str;

/**
 * Class loginController
 * @package app\http\api
 */
class loginController extends HttpController{

    /**
     * 登录
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        $username = $this->getPOSTData("trim:username", null, "缺少登录账号");
        $password = $this->getPOSTData("password", null, "缺少登录密码");
        //$verify_code = $this->getPOSTData("verify_code", null,"请填写验证码");
        $username = Str::trim($username);
        if (empty($username)) {
            $this->response("缺少登录账号");
        }
        /*
        if (!matchVerifyCode($verify_code)) {
            $this->response("验证码错误：");
        }
        Request::dropSession("verify_code");
        */
        $u = new User(false);
        if ($info = $u->login($username, $password)) {
            $this->response(0, '登录成功', [
                'token'     => $info['token']
            ]);
        } else {
            $this->response($u->errorInfo);
        }
    }
}