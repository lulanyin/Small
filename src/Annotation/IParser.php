<?php
namespace Small\annotation;

/**
 * 注解处理类的接口类，实现它即可
 * Interface IParser
 * @package Small\annotation
 */
interface IParser{

    /**
     * 统一处理入口
     * @param $class
     * @param string $target
     * @param string $targetType
     * @return mixed
     */
    public function process($class, string $target, string $targetType);
}