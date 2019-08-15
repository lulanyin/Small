<?php
namespace Small\Annotation\Parser;

use Small\Annotation\IParser;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class After
 * @package Small\Annotation\Parser
 */
class After implements IParser {

    /**
     * 处理类
     * @var array
     */
    private $class;

    /**
     * After constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        if(isset($values['value'])){
            $this->class = is_array($values['value']) ? $values['value'] : [$values['value']];
        }
    }

    /**
     * 需要数据的数据
     */
    private $result = null;

    /**
     * 设置数据
     * @return After
     */
    public function setResult($result){
        $this->result = $result;
        return $this;
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
        if(class_exists($this->class)){
            $obj = new $this->class();
            if(method_exists($obj, "after")){
                $obj->after([
                    "class"     => $class,
                    "target"    => $target,
                    "targetType"=> $targetType,
                    "data"      => $this->result
                ]);
            }
        }
    }
}