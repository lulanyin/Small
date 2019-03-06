<?php
/**
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2019-03-06
 * Time: 19:02
 */
namespace Small\server\session;

use Small\middleware\IMiddleWare;

/**
 * 实现Session的中间键
 * Class SessionMiddleWare
 * @package Small\server\session
 */
class SessionMiddleWare implements IMiddleWare{

    public function process($controller, ...$args)
    {
        print_r($args);
        // TODO: Implement process() method.
        echo 'session middleware process'.PHP_EOL;
        return $controller;
    }
}