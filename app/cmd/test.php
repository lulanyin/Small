<?php
namespace app\cmd;

use Small\commend\ICommend;

class test implements ICommend{

    public function __construct(array $params = null)
    {
        //parent::__construct($params);
        echo "参数：".PHP_EOL;
        print_r($params);
    }

    public function run()
    {
        // TODO: Implement run() method.
        echo "这是一个测试命令，执行结束...!".PHP_EOL;
    }
}