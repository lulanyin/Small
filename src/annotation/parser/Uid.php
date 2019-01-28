<?php
namespace Small\annotation\parser;

use Small\annotation\IParser;
use Small\lib\cache\Cache;
use Small\lib\util\Request;
use Doctrine\Common\Annotations\Annotation\Target;
use Small\server\http\RequestController;

/**
 * 注入当前TOKEN资料中的UID给属性
 * @Annotation
 * @Target("PROPERTY")
 * Class Uid
 * @package Small\annotation\parser
 */
class Uid implements IParser {

    /**
     * @var int
     */
    private $uid = 0;

    /**
     * Uid constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {

    }

    /**
     * 实现注解处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target, string $targetType)
    {
        // TODO: Implement process() method.
        if($class instanceof RequestController){
            $token = $class->getQueryString('token', $class->getPostData('token', $class->getCookie('token')));
        }else{
            $token = Request::get("token", Request::post('token', Request::getCookie('token', Request::getSession('token'))));
        }
        if(!empty($token)) {
            if ($info = Cache::get($token)) {
                //判断过期时间
                $expTime = $info['exp_time'] ?? 0;
                if ($expTime > time()) {
                    $this->uid = $info['uid'];
                }
            }
        }
        //赋值给属性
        $class->{$target} = $this->uid;
    }
}