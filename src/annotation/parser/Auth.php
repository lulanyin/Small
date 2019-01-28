<?php
namespace Small\annotation\parser;

use Small\annotation\IParser;

use Small\http\HttpController;
use Small\lib\cache\Cache;
use Small\lib\util\Request;
use Small\model\models\LoginHistoryModel;
use Doctrine\Common\Annotations\Annotation\Target;
use Small\server\http\RequestController;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class Auth
 * @package Small\annotation\parser
 */
class Auth implements IParser {

    /**
     * @var string
     */
    public $group = null;

    /**
     * @var int
     */
    public $level = null;

    /**
     *
     * @var null
     */
    public $inject = null;

    /**
     *
     * @var null
     */
    public $uid = null;

    public function __construct(array $values)
    {
        if(isset($values['value'])){
            $this->group = $values['value'];
        }
        if(isset($values['group'])){
            $this->group = $values['group'];
        }
        if(isset($values['level'])){
            $this->level = $values["level"];
        }
        if(isset($values['inject'])){
            $this->inject = $values["inject"];
        }
        if(isset($values['uid'])){
            $this->uid = $values["uid"];
        }
    }

    /**
     * 实现注解处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target = null, string $targetType = null)
    {
        // TODO: Implement process() method.
        // 首先检测登录情况
        // 获取token
        if($class instanceof RequestController){
            $token = $class->getQueryString('token', $class->getPostData('token', $class->getCookie('token')));
        }else{
            $token = Request::get("token", Request::post('token', Request::getCookie('token', Request::getSession('token'))));
        }
        if(!empty($token)){
            if($info = Cache::get($token)){
                //判断过期时间
                $expTime = $info['exp_time'] ?? 0;
                if($expTime > time()){
                    //还在登录有效期
                    //检查
                    $bool1 = true;
                    if(!is_null($this->group)){
                        $bool1 = $info['group'] == $this->group;
                    }
                    $bool2 = true;
                    if(!is_null($this->level)){
                        $bool2 = $info['level'] >= $this->level;
                    }
                    //判断是否都通过检测
                    if(!$bool2 || !$bool1){
                        //无权限
                        if($class instanceof HttpController || $class instanceof RequestController){
                            if($class->isAjaxMethod()){
                                $class->response(-1, lang("framework.auth.105"));
                            }else{
                                //跳转到登录
                                $class->redirect("login");
                            }
                        }
                    }else{
                        //更新登录记录，以保证能持续有效
                        $hm = new LoginHistoryModel();
                        $hm->saveToken($info['uid'], $info['exp_seconds'], $token);
                        Cache::update($token, [
                            "exp_time"  => time() + $info['exp_seconds']
                        ]);
                        //
                        if($this->inject){
                            if(property_exists($class, $this->inject)){
                                $class->{$this->inject} = $info;
                            }
                        }
                        if($this->uid){
                            if(property_exists($class, 'uid')){
                                $class->uid = $info['uid'];
                            }
                        }
                    }
                    return;
                }else{
                    Cache::remove($token);
                }
            }
        }
        //未登录，将触发跳转
        if($class instanceof HttpController || $class instanceof RequestController){
            if($class->isAjaxMethod()){
                $class->response(-1, lang("framework.not login"));
            }else{
                //跳转到登录
                $class->redirect("login");
            }
        }
    }
}