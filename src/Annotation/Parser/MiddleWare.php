<?php
namespace Small\Annotation\Parser;

use Small\Annotation\IParser;
use Small\Middleware\IMiddleWare;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class MiddleWare
 * @package Small\Annotation\Parser
 */
class MiddleWare implements IParser {

    /**
     * @var string
     */
    private $class = '';

    /**
     * MiddleWare constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        if(isset($values['value'])){
            $this->class = $this->trimClass($values['value']);
        }
        if(isset($values['class'])){
            $this->class = $this->trimClass($values['class']);
        }
    }

    /**
     * @param string $value
     * @return string
     */
    protected function trimClass(string $value)
    {
        return stripos($value, "\\")===0 ? substr($value, 1) : $value;
    }

    /**
     * 获取中间键的类名
     * @return string
     */
    public function getClass() : string {
        return $this->class;
    }

    /**
     * 实现注解的处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target, string $targetType)
    {
        // TODO: Implement process() method.
        // 中间键触发
        $middleware = new $this->class();
        if($middleware instanceof IMiddleWare){
            $middleware->process($class, $target, $targetType);
        }
    }
}