<?php
namespace Small\annotation;

use Small\annotation\parser\After;
use Small\http\HttpController;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationParser{

    public static $annotations = [];

    /**
     * 处理注解
     * @param HttpController $class
     * @param string $method
     * @return IParser[]
     */
    public static function parse($class, $method){
        try{
            $refClass = new \ReflectionClass($class);
            try{
                $reader = new AnnotationReader();
                if(!isset(static::$annotations[$refClass->name])){
                    //获取类的所有注解
                    static::$annotations[$refClass->name]['class'] = $reader->getClassAnnotations($refClass);
                    //获取所有属性
                    $properties = $refClass->getProperties();
                    static::$annotations[$refClass->name]['property'] = [];
                    foreach ($properties as $property){
                        //获取属性的所有注解
                        static::$annotations[$refClass->name]['property'][$property->name] = $reader->getPropertyAnnotations($property);
                    }
                    //设置空数组
                    static::$annotations[$refClass->name]['method'] = [];
                    //回收变量
                    unset($properties);
                }
                //获取执行方法的注解
                $method = $refClass->getMethod($method);
                static::$annotations[$refClass->name]['method'][$method->name] = $reader->getMethodAnnotations($method);
                //处理当前方法使用到的所有注解
                $after = static::process($class, $refClass->name, $method->name);
                //回收变量
                unset($refClass);
                unset($reader);
                unset($method);
                return $after;
            }catch (AnnotationException $annotationException){
                exit("AnnotationException : ".$annotationException->getMessage().PHP_EOL);
            }
        }catch (\ReflectionException $e){
            exit("ReflectionException : ".$e->getMessage().PHP_EOL);
        }
    }

    /**
     * 处理注解
     * @param $class
     * @param $className
     * @param $method
     * @return IParser[]
     */
    public static function process($class, $className, $method)
    {
        if (isset(static::$annotations[$className])) {
            $annotations = static::$annotations[$className];
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