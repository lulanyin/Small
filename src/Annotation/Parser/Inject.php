<?php
namespace Small\Annotation\Parser;

use Doctrine\Common\Annotations\Annotation\Target;
use Small\Annotation\IParser;
use Small\Http\HttpResponse;
use Small\View\View;
use Small\Http\HttpController;

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
        if($class === HttpResponse::class){
            $class->{$target} = App::getContext("HttpResponse");
        }elseif($class === View::class){
            $class->{$target} = App::getContext("View");
        }elseif($class === HttpController::class){
            $class->{$target} = App::getContext("HttpController");
        }else{
            $targetClass = new $this->name();
            if(method_exists($targetClass, "Inject")){
                $targetClass->Inject($class, $target, $targetType);
            }
        }
    }
}