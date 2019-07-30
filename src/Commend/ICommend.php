<?php
namespace Small\Commend;

/**
 * 用于PHP CLI运行的接口类，实现它即可
 * Interface ICommend
 * @package Small\commend
 */
interface ICommend{

    /**
     * 初始化
     * ICommend constructor.
     * @param array|null $params
     */
    public function __construct(array $params = null);

    /**
     * 执行命令
     * @return mixed
     */
    public function run();
}