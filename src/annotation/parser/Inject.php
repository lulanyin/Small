<?php
namespace Small\annotation\parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\annotation\IParser;

/**
 * @Annotation
 * @Target("PROPERTY")
 * Class Inject
 * @package app\lib\annotation\parser
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
        $targetClass = new $this->name();
        if(method_exists($targetClass, "Inject")){
            $targetClass->Inject($class, $target, $targetType);
        }
    }
}