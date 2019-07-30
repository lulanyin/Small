<?php
namespace Small\Annotation\Parser;

use Small\Annotation\IParser;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class After
 * @package Small\annotation\parser
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
     * 实现注解处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target, string $targetType)
    {
        // TODO: Implement process() method.

    }
}