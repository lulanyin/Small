<?php
namespace Small\annotation;

/**
 * 注解处理类的接口类，实现它即可
 * Interface IParser
 * @package Small\annotation
 */
interface IParser{
    public function process($class, string $target, string $targetType);
}