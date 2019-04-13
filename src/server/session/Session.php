<?php
/**
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2019-03-06
 * Time: 18:58
 */
namespace Small\server\session;

use Small\lib\cache\Cache;
use Small\lib\util\Str;
use Small\server\http\Cookie;
use Small\server\http\RequestController;

/**
 * 使用Redis+Cookie来模拟Session
 * Class Session
 * @package Small\server\session
 */
class Session {

    private $ctrl;
    /**
     * @var string|null
     */
    private $id;

    /**
     * Session constructor.
     * @param RequestController $controller
     */
    public function __construct(RequestController $controller)
    {
        $this->ctrl = $controller;
        $php_session_id = is_array($this->ctrl->request->cookie) ? ($this->ctrl->request->cookie['PHP_SESSION_ID'] ?? NULL) : NULL;
        if(is_null($php_session_id)){
            $php_session_id = sha1(microtime(true) . Str::randomNumber(12));
            $this->ctrl->response->withCookie(new Cookie(
                'PHP_SESSION_ID',
                $php_session_id,
                time() + 3600,
                '/',
                null,
                null,
                true
            ));
        }
        $this->id = $php_session_id;
    }

    /**
     * 保存
     * @param $name
     * @param $value
     */
    public function set($name, $value){
        Cache::update($this->id, [
            $name => $value
        ]);
    }

    /**
     * 获取
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function get($name, $default = null){
        $cache = Cache::get($this->id);
        if(is_array($cache)){
            return $cache[$name] ?? $default;
        }
        return $default;
    }

    /**
     * 销毁
     * @param $name
     */
    public function drop($name){
        Cache::update($this->id, [
            $name => null
        ]);
    }

    /**
     * 保存验证码
     * @param $value
     */
    public function setVerifyCode($value){
        $this->set("verify_code", $value);
    }

    /**
     * 验证验证码
     * @param $value
     * @return bool
     */
    public function matchVerifyCode($value){
        $val = $this->get("verify_code");
        return !is_null($val) && strtolower($val) == strtolower($value);
    }
}