<?php
namespace Small\lib\auth;

use Small\Config;
use Small\model\models\UserModel;

class User extends AuthUser{

    /**
     * 实现Inject注解类的处理，Inject(User::class)
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function Inject($class, string $target, string $targetType)
    {
        // TODO: Implement process() method.
        switch ($targetType){
            case "property" :
                $class->{$target} = $this->userInfo;
                break;
            case "class" :
                if(property_exists($class, "user")){
                    $class->user = $this->userInfo;
                }
                if(property_exists($class, "userInfo")){
                    $class->userInfo = $this->userInfo;
                }
                break;
        }
    }

    /**
     * 静态方法数据
     * @var array
     */
    public static $staticUserInfo = [];

    /**
     * 静态方法检测登录
     * @param null $group
     * @return bool
     */
    public static function isLogin($group = null){
        if(empty(static::$staticUserInfo)){
            $class = new static();
            if($class->isLogin){
                static::$staticUserInfo = $class->userInfo;
            }else{
                return false;
            }
        }
        return !is_null($group) ? static::$staticUserInfo['group_type']==$group : true;
    }

    /**
     * 检测二级密码
     * @param int $uid
     * @param string $value
     * @return bool
     */
    public static function checkSafePassword(int $uid, string $value){
        $m = new UserModel();
        $sp = $m->mainQuery('u')
            ->where("u.uid", $uid)
            ->pluck("sp");
        return md5(md5($value).$uid) == $sp;
    }

    /**
     * 更改二级密码
     * @param $uid
     * @param $newPassword
     */
    public static function updateSecondPassword($uid, $newPassword){
        $m = new UserModel();
        @$m->mainQuery()
            ->where("uid", $uid)
            ->update([
                "sp"    => md5(md5($newPassword).$uid)
            ]);
    }

    public static function checkRegisterIP($ip){
        $userSet = Config::get("private.user");
        if($userSet['same_ip_limit']>0){
            $m = new UserModel();
            $rows = $m->mainQuery()
                ->where("register_ipv4", $ip)
                ->whereDateTimeStartAt("register_time", time()-86400)
                ->rows();
            return $rows<=$userSet['same_ip_limit'];
        }
        return true;
    }
}