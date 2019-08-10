<?php
/**
 * Create By Hunter
 * 2019-07-30 02:35:51
 */
namespace Small\Http;

use Small\Config;
use Small\Util\Arr;
use Small\Util\Str;

/**
 *
 * Class Request
 * @package Small\Http
 */
class Request{

    /**
     * GET, POST取值统一处理
     * @param $var
     * @param $name
     * @param string $default
     * @return array|int|mixed|string|null
     */
    private static function request(&$var, $name, $default = '')
    {
        $fn = null;
        if(stripos($name, ":")>0){
            list($fn, $name) = explode(":", $name, 2);
        }
        $value = Arr::get($var, $name, $default);
        $value = empty($value) && !is_numeric($value) ? $default : $value;
        $var[$name] = $fn ? Str::filter($fn, $value) : $value;
        return $value;
    }

    /**
     * 获得地址参数$_GET的某个参数值
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public static function get($name, $default=''){
        return self::request($_GET, $name, $default);
    }

    /**
     * 获得$_POST的某个参数值
     * @param $name
     * @param string $default
     * @return mixed
     */
    public static function post($name, $default=''){
        return self::request($_POST, $name, $default);
    }

    /**
     * 设置某个cookie值
     * @param $name
     * @param null $value
     * @param int $time
     * @param string $path
     */
    public static function setCookie($name, $value=null, $time=60, $path=NULL){
        if(!empty($name)){
            $setting = Config::get("server.setting") ?? [];
            $hash = $setting["cookies_hash_key"] ?? '1q2w3e4v5b6n';
            $domain_set = Config::get('domain');
            if(!empty($domain_set)){
                //有域名设置
                $https = $domain_set["scheme"] == "https";
                $domain = $domain_set["host"];
                $path = is_null($path) ? Arr::get($setting, "cookies_path") : $path;
                $cookie_domains = $domain_set['cookie_domains'] ?? [];
                $cookie_domains = is_array($cookie_domains) ? $cookie_domains : [$cookie_domains];
                $cookie_domains[] = $domain;
                foreach ($cookie_domains as $cd)
                {
                    setcookie($name, $value, time()+$time, $path, $cd, $https, true);
                    setcookie($name."_hash", substr(md5($value.$hash),0,16), time()+$time, $path, $cd, $https, true);
                }
            }else{
                //无域名，则直接保存即可
                $path = $path ?? '/';
                setcookie($name, $value, time()+$time, $path);
                setcookie($name."_hash", substr(md5($value.$hash),0,16), time()+$time, $path);
            }
        }
    }

    /**
     * 获取某个cookie值
     * @param string $name
     * @param string $defaultValue
     * @return string
     */
    public static function getCookie($name="", $defaultValue=null){
        if( !isset($_COOKIE[$name]) || !isset($_COOKIE[$name.'_hash']) ){
            return $defaultValue;
        }else{
            $setting = Config::get("server.setting") ?? [];
            $hash = $setting["cookies_hash_key"] ?? '1q2w3e4v5b6n';
            if($_COOKIE[$name.'_hash']!=substr(md5($_COOKIE[$name].$hash),0,16)){
                return null;
            }else{
                return $_COOKIE[$name];
            }
        }
    }

    /**
     * 失效Cookie值
     * @param string $name
     * @param string $path
     */
    public static function dropCookie($name="", $path=NULL){
        if(!empty($name)){
            self::setCookie($name, null, -1, $path);
        }
    }

    /**
     * 设置某个session值
     * @param $name
     * @param null $value
     */
    public static function setSession($name, $value=null){
        @session_start();
        $_SESSION[$name] = $value;
    }

    /**
     * @param $name
     * @param null $default
     * @return string|null
     */
    public static function getSession($name, $default=null){
        @session_start();
        return Arr::get($_SESSION, $name, $default);
    }

    /**
     * 删除Session
     * @param $name
     */
    public static function dropSession($name){
        @session_start();
        if(isset($_SESSION[$name])) unset($_SESSION[$name]);
    }

    /**
     * 验证验证码
     * @param $val
     * @param string $name
     * @return bool
     */
    public static function verifyCode($val, $name = "verify_code"){
        return strtolower($val)==strtolower(self::getSession($name, "-1-")) && !empty($val);
    }

    /**
     * 检测是不是AJAX访问
     * @return bool
     */
    public static function isAjaxMethod(){
        $http_x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        return $http_x_requested_with == "XMLHttpRequest";
    }
}