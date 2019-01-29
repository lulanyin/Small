<?php
namespace Small\lib\auth;

use Small\Lang;
use Small\lib\cache\Cache;
use Small\lib\httpMessage\Request;
use Small\lib\util\Str;
use Small\model\models\LoginHistoryModel;
use Small\model\models\UserGroupModel;
use Small\model\models\UserModel;
use Small\server\http\RequestController;

class AuthUser{

    /**
     * 账号的资料
     * @var array
     */
    public $userInfo = [
        "uid"       => 0,
        "group_id"  => 0,
        "nickname"  => '',
        "username"  => '',
        "email"     => '',
        "phone"     => '',
        "level"     => -1,
        "group_type"=> ''
    ];

    /**
     * 登录有效时间，单位：秒
     * @var int
     */
    public $expTime = 86400;

    /**
     * 错误码
     * @var int
     */
    public $errorCode = 0;

    /**
     * 错误说明
     * @var string
     */
    public $errorInfo = "";

    /**
     * 密码是否已经过MD5
     * @var bool
     */
    public $md5Pwd = false;

    /**
     * 是否已登录成功
     * @var bool
     */
    public $isLogin = false;

    /**
     * 最高权限使用
     * @var bool
     */
    public $admin = false;

    //2019.1.29、新增
    /**
     * @var RequestController
     */
    public $controller = null;

    /**
     * 实例化时，可以选择自动检测登录
     * AuthUser constructor.
     * @param bool $autoLogin
     * @param int $expTime
     * @param RequestController $controller
     */
    public function __construct($autoLogin = true, $expTime = 86400, RequestController $controller = null)
    {
        $this->controller = $controller;
        $this->expTime = is_numeric($autoLogin) && $autoLogin>0 ? $autoLogin : $expTime;
        if($autoLogin){
            //优选获取：$_GET['token'] -> $_POST['token'] -> $_COOKIE['token'] -> $_SESSION['token']
            if(null != $this->controller){
                $token = $this->controller->getQueryString('token',
                    $this->controller->getPostData('token',
                        $this->controller->getCookie('token')));
            }else{
                $token = Request::get("token", Request::post("token", Request::getCookie("token", Request::getSession("token"))));
            }
            if(!empty($token) && strlen($token)===32){
                $this->loginByToken($token, $this->expTime);
            }
        }
    }


    /**
     * 使用TOKEN进行登录校验
     * @param string $token
     * @param int $expTime
     * @return array|null
     */
    public function loginByToken(string $token, int $expTime = 86400){
        $this->expTime = $expTime;
        //从缓存读取
        if($cacheInfo = Cache::get($token)){
            //存在缓存数据
            $now = time();
            if($cacheInfo['exp_time'] > $now){
                //未过期
                if($userInfo = $this->getUidInfo($cacheInfo['uid'])){
                    //获取到会员完整数据
                    //验证数据可用性
                    if($this->checkInfo($userInfo)){
                        //数据可用，更新此账号的登录情况
                        return $this->updateLogin($userInfo, $token);
                    }
                }
            }
        }
        return null;
    }

    /**
     * 正常登录，使用账号、密码
     * @param string $account
     * @param string $password
     * @return array|null
     */
    public function login(string $account, string $password){
        $account = explode(PHP_EOL, $account, 1)[0];
        $account = Str::trim($account);
        $field = Str::isEmail($account) ? "email" : (
            Str::isPhoneNumber($account) ? "phone" : (
                Str::isLettersOrNumbersOrUnderscores($account) ? "username" : "nickname"
            )
        );
        //得到完整会员数据
        $tempInfo = $this->getInfo(["u.{$field}", $account]);
        if(!empty($tempInfo)){
            $hm = new LoginHistoryModel();
            //1小时内，如果超过错误次数，则直接禁止登录
            if($hm->getErrorHistory($tempInfo['uid'], 3600) < 5){
                //验证密码
                if($this->matchPassword($password, $tempInfo['password'], $tempInfo['safe_code'])){
                    if($this->checkInfo($tempInfo)){
                        return $this->updateLogin($tempInfo);
                    }else{
                        //账号已被禁止登录
                        $this->setError(106);
                    }
                }else{
                    //密码错误，记录错误
                    $hm->saveHistory($tempInfo['uid'], 101);
                    $this->setError(101);
                }
            }else{
                //频繁多次登录错误，禁止
                $this->setError(102);
            }
        }else{
            //找不到账号
            $this->setError(104);
        }
        return null;
    }

    /**
     * 常规注册
     * @param string $account
     * @param string $password
     * @param string $groupType
     * @param array $otherFields
     * @return false|int
     */
    public function register(string $account, string $password, string $groupType, $otherFields = []){
        $account = explode(PHP_EOL, $account, 1)[0];
        $account = Str::trim($account);
        $field = Str::isEmail($account) ? "email" : (
            Str::isPhoneNumber($account) ? "phone" : (
                Str::isLettersOrNumbersOrUnderscores($account) ? "username" : "nickname"
            )
        );
        //
        if($field=='username' && strlen($account)<6){
            //账号格式错误
            $this->setError(201);
        }elseif($field=='nickname' && strlen($account)<2){
            //账号格式错误
            $this->setError(201);
        }elseif(strlen($password)<6 || strlen($password)>24){
            //密码格式错误
            $this->setError(202);
        }else{
            //检测账号是否已被注册
            $um = new UserModel();
            $rows = $um->mainQuery("u")
                ->where("u.{$field}", $account)->rows();
            $rows = !empty($rows) ? $rows : 0;
            if($rows==0){
                //查会员组
                $gm = new UserGroupModel();
                $group = $gm->mainQuery()
                    ->where("group_type", $groupType)
                    ->first();
                if(!empty($group)){
                    //分组是否允许注册
                    if($group['open_register']==1 || $this->admin){
                        //开放注册，开始组合数据
                        //密码二次加密字符
                        $safe_code = Str::randomString(6);
                        $md5Password = $this->md5Pwd ? md5($password.$safe_code) : md5(md5($password).$safe_code);
                        $newUser = [
                            "group_id"      => $group['group_id'],
                            $field          => $account,
                            "password"      => $md5Password,
                            "safe_code"     => $safe_code,
                            "register_ipv4" => getIP()
                        ];
                        if(!empty($otherFields)){
                            //为避免错误，不可重复设置字段
                            foreach ($newUser as $key=>$value){
                                if(isset($otherFields[$key])){
                                    unset($otherFields[$key]);
                                }
                            }
                            $newUser = array_merge($newUser, $otherFields);
                        }
                        //执行注册
                        $db = $um->mainQuery();
                        if($db->insert($newUser)){
                            //注册成功，获取最新UID
                            $newUid = $db->getLastInsertId();
                            //返回最新的UID
                            return $newUid;
                        }else{
                            //注册失败
                            $this->setError(206);
                        }
                    }else{
                        //分级不允许注册
                        $this->setError(205);
                    }
                }else{
                    //注册的分组不存在
                    $this->setError(204);
                }
            }else{
                //注册账号已存在
                $this->setError(203);
            }
        }
        return false;
    }

    /**
     * 缓存登录
     * @param array $userInfo
     * @param null $token
     * @return array
     */
    private function updateLogin(array $userInfo, $token = null){
        //登录用的数据库
        $hm = new LoginHistoryModel();
        //标记，已登录
        $this->isLogin = true;
        //当前时间
        $now = time();
        //保存到数据库
        $token = $hm->saveToken($userInfo['uid'], $this->expTime, $token);
        //保存到缓存、Cookie、Session
        Cache::set($token, [
            "uid"       => $userInfo['uid'],
            "group"     => $userInfo['group_type'],
            "level"     => isset($userInfo['level']) ? $userInfo['level'] : -1,
            "exp_seconds" => $this->expTime,
            "exp_time"  => $now + $this->expTime
        ]);
        Request::setSession("token", $token);
        Request::setCookie("token", $token, $this->expTime);
        //返回新数据
        $userInfo['token'] = $token;
        //更新成员数据
        $this->userInfo = $userInfo;
        return $userInfo;
    }

    /**
     * 由UID获取账号完整数据
     * @param $uid
     * @return array|null
     */
    private function getUidInfo($uid){
        return $this->getInfo(["u.uid", $uid]);
    }

    /**
     * 由特定条件获取账号完整数据
     * @param $where
     * @return array|null
     */
    private function getInfo($where){
        $m = new UserModel();
        $full_data = $m->where($where)->first();
        return !empty($full_data) ? $full_data : null;
    }

    /**
     * 检测账号的可用性
     * @param array $info
     * @return bool
     */
    private function checkInfo(array $info){
        $frozen = isset($info['frozen']) ? $info['frozen'] : 0;
        $destroy = isset($info['destroy']) ? $info['destroy'] : 0;
        return $frozen==0 && $destroy==0;
    }

    /**
     * 匹配密码
     * @param $password
     * @param $md5Password
     * @param $md5Key
     * @return bool
     */
    private function matchPassword($password, $md5Password, $md5Key){
        if(!$this->md5Pwd || strlen($password)!==32){
            $password = md5($password);
        }
        return $md5Password==md5($password.$md5Key);
    }

    /**
     * 设置错误
     * @param $code
     */
    private function setError($code){
        $this->errorCode = $code;
        $this->errorInfo = Lang::getValue("framework.auth.{$code}");
    }

    /**
     * 获取错误说明
     * @return string
     */
    public function getError(){
        return $this->errorInfo;
    }

    /**
     * 退出登录
     */
    public function logout(){
        if($this->isLogin){
            $token = $this->userInfo['token'];
        }else{
            $token = Request::getCookie("token", Request::getSession("token"));
        }
        if(!empty($token)){
            Cache::remove($token);
            Request::dropCookie("token");
            Request::dropSession("token");
            //清除登录
            $hm = new LoginHistoryModel();
            $hm->mainQuery()
                ->whereDateTimeStartAt("exp_time", time())
                ->where("token", $token)
                ->update(["exp_time"=>time2datetime(time()-1)]);
        }
    }


    /**
     * 注册时自动生成用户名
     * @param null $suffix
     * @return string
     */
    public function getUserName($suffix=null){
        $str = Str::randomWord(2);
        $str .= Str::randomNumber(8);
        $userName = $str.$suffix;
        $u = new UserModel();
        $rows = $u->mainQuery()->whereRaw("`username` = '{$userName}'")->rows();
        if($rows>0){
            return $this->getUserName($suffix);
        }
        return $userName;
    }

    /**
     * 更改密码
     * @param $uid
     * @param $newPassword
     * @return bool
     */
    public function updatePassword($uid, $newPassword){
        if(strlen($newPassword)<6){
            $this->errorInfo = "密码长度不能少于6位";
            return false;
        }
        $safe_code = Str::randomString(6);
        $md5Password = $this->md5Pwd ? md5($newPassword.$safe_code) : md5(md5($newPassword).$safe_code);
        $um = new UserModel();
        $db = $um->mainQuery()
            ->where('uid', $uid)
            ->set([
                'password' => $md5Password,
                'safe_code' => $safe_code
            ]);
        if($db->update()){
            return true;
        }else{
            $this->errorInfo = "修改密码失败，请稍后重试";
            return false;
        }
    }
}