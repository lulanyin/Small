<?php
namespace Small\annotation\parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\annotation\IParser;
use Small\lib\auth\AuthUser;
use Small\lib\auth\User;
use Small\server\http\HttpController;

/**
 * @Annotation
 * @Target("PROPERTY")
 * Class Inject
 * @package Small\annotation\parser
 */
class Inject implements IParser {

    /**
     * 注入的类名
     * @var mixed|string
     */
    public $name = '';

    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->name = $values['value'];
        }
        if (isset($values['name'])) {
            $this->name = $values['name'];
        }
    }

    /**
     * 获取注入的类名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 实现注解的处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target, string $targetType)
    {
        if($this->name == User::class){
            if($class instanceof HttpController){
                $targetClass = new $this->name(true, 86400, $class);
            }else{
                $targetClass = new $this->name();
            }
        }else{
            $targetClass = new $this->name();
        }
        if(method_exists($targetClass, "Inject")){
            $targetClass->Inject($class, $target, $targetType);
        }
    }
}