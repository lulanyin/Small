<?php
/**
 * Create By Hunter
 * 2019-08-20 18:43:36
 */
namespace Small\Annotation;

use Small\Annotation\Parser\After;

/**
 * 注解快捷处理类
 * Class Annotation
 * @package Small\Annotation
 */
class Annotation
{
    /**
     * 统一处理
     * @param $class
     * @param $method
     * @return mixed|null
     */
    public static function process($class, $method = null){
        $annotation = new AnnotationParser($class, $method);
        $afterParsers = $annotation->parse();
        if(!is_null($method)){
            $result = $class->{$method}();
        }else{
            $result = null;
        }
        if(!empty($afterParsers)){
            foreach ($afterParsers as $parser){
                if($parser instanceof After){
                    $parser->setResult($result)->process($class, $method, 'method');
                    $result = $parser->getResult();
                }else{
                    $parser->process($class, $method, 'method');
                }
            }
        }
        return $result;
    }
}