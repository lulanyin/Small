<?php
namespace Small\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;
use Small\Annotation\Parser\After;

class AnnotationParser{

    public $annotations = [];
    
    public $class;
    public $method = null;

    /**
     * AnnotationParser constructor.
     * @param $class
     * @param null $method
     */
    public function __construct($class, $method = null)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * 处理注解
     * @return IParser[]
     */
    public function parse(){
        $class = $this->class;
        $method = $this->method;
        try{
            $refClass = new ReflectionClass($class);
            try{
                $reader = new AnnotationReader();
                if(!isset($this->annotations[$refClass->name])){
                    //获取类的所有注解
                    $this->annotations[$refClass->name]['class'] = $reader->getClassAnnotations($refClass);
                    //获取所有属性
                    $properties = $refClass->getProperties();
                    $this->annotations[$refClass->name]['property'] = [];
                    foreach ($properties as $property){
                        //获取属性的所有注解
                        $this->annotations[$refClass->name]['property'][$property->name] = $reader->getPropertyAnnotations($property);
                    }
                    //设置空数组
                    $this->annotations[$refClass->name]['method'] = [];
                    //回收变量
                    unset($properties);
                }
                if($method !== null){
                    //获取执行方法的注解
                    $refMethod = $refClass->getMethod($method);
                    $this->annotations[$refClass->name]['method'][$refMethod->name] = $reader->getMethodAnnotations($refMethod);
                    //处理当前方法使用到的所有注解
                    $after = $this->process($class, $refClass->name, $refMethod->name);
                    //回收变量
                    unset($refClass);
                    unset($reader);
                    unset($refMethod);
                    return $after;
                }else{
                    unset($refClass);
                    unset($reader);
                    return [];
                }
            }catch (AnnotationException $annotationException){
                echo "AnnotationException : ".$annotationException->getMessage().PHP_EOL;
            }
        }catch (ReflectionException $e){
            echo "ReflectionException : ".$e->getMessage().PHP_EOL;
        }
        return null;
    }

    /**
     * 处理注解
     * @param $class
     * @param $className
     * @param $method
     * @return IParser[]
     */
    public function process($class, $className, $method)
    {
        if (isset($this->annotations[$className])) {
            $annotations = $this->annotations[$className];
            //处理类的注解
            if (!empty($annotations['class'])) {
                foreach ($annotations['class'] as $classAnnotation) {
                    if ($classAnnotation instanceof IParser) {
                        $classAnnotation->process($class, 'class', 'class');
                    }
                }
            }
            //处理属性的注解
            if (!empty($annotations['property'])) {
                foreach ($annotations['property'] as $property => $propertyAnnotations) {
                    foreach ($propertyAnnotations as $propertyAnnotation) {
                        if ($propertyAnnotation instanceof IParser) {
                            $propertyAnnotation->process($class, $property, 'property');
                        }
                    }
                }
            }
            $after = [];
            //处理本次使用方法的注解
            if (!empty($annotations['method'])) {
                if (!empty($annotations['method'][$method])) {
                    foreach ($annotations['method'][$method] as $methodAnnotation) {
                        if ($methodAnnotation instanceof After) {
                            $after[] = $methodAnnotation;
                        } elseif ($methodAnnotation instanceof IParser) {
                            $methodAnnotation->process($class, $method, 'method');
                        }
                    }
                }
            }
            //回收变量
            unset($annotations);
            return $after;
        }
        return [];
    }
}