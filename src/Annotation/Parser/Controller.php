<?php
namespace Small\Annotation\Parser;

use Small\Annotation\IParser;
use Doctrine\Common\Annotations\Annotation\Target;
use Small\Http\HttpController;
use Small\App;

/**
 * @Annotation
 * @Target("CLASS")
 * Class After
 * @package Small\Annotation\Parser
 */
class Controller implements IParser
{

    public $instance = null;

    /**
     * After constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        if(isset($values['value'])){
            $this->instance = is_array($values['value']) ? $values['value'][0] : $values['value'];
        }
        if(class_exists($this->instance)){
            $instance = new $this->instance();
            if($this->instance instanceof HttpController){
                App::setContext("HttpController", $instance);
            }
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