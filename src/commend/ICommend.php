<?php
namespace Small\commend;

/**
 * 用于PHP CLI运行的接口类，实现它即可
 * Interface ICommend
 * @package Small\commend
 */
interface ICommend{
    public function __construct(array $params = null);
    public function run();
}