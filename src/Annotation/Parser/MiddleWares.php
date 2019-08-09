<?php
namespace Small\Annotation\Parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\Annotation\IParser;
use Small\Middleware\IMiddleWare;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * Class MiddleWares
 * @package Small\Annotation\Parser
 */
class MiddleWares implements IParser {

    /**
     * @var array
     */
    private $middleWares = [];

    /**
     * @var string
     */
    private $group = '';

    /**
     * Middlewares constructor.
     *
     * @param array $values
     */
    public function __construct($values)
    {
        if (isset($values['value'])) {
            $this->middleWares = $values['value'];
        }
        if (isset($values['middlewares'])) {
            $this->middleWares = $values['middlewares'];
        }
        if (isset($values['group'])) {
            $this->group = $values['group'];
        }
    }

    /**
     * @return array
     */
    public function getMiddleWares(): array
    {
        return $this->middleWares;
    }

    /**
     * @param array $middleWares
     * @return Middlewares
     */
    public function setMiddleWares($middleWares)
    {
        $this->middleWares = $middleWares;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
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
        foreach ($this->getMiddleWares() as $middleWare){
            $mdw = new $middleWare();
            if($mdw instanceof IMiddleWare){
                $mdw->process($class, $target, $targetType);
            }
        }
    }
}