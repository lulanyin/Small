<?php
namespace Small\Annotation\Parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\Annotation\IParser;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class View
 * @package Small\Annotation\Parser
 */
class View implements IParser{

    public $template;

    public function __construct(array $values)
    {
        if(isset($values['template'])){
            $this->template = is_string($values['template']) ? $values['template'] : [$values['template']];
        }
        elseif(isset($values['value'])){
            $this->template = is_string($values['value']) ? $values['value'] : [$values['value']];
        }
    }

    public function process($class, string $target, string $targetType)
    {
        // TODO: Implement process() method.
        if(property_exists($class, 'template')){
            $class->template = $this->template;
        }
    }
}