<?php
namespace Small\annotation\parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\annotation\IParser;
use Small\http\HttpController;
use Small\server\http\RequestController;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class View
 * @package Small\annotation\parser
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
        if($class instanceof HttpController || $class instanceof RequestController){
            $class->template = $this->template;
        }
    }
}