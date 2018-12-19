<?php
namespace app\http\web;

use Small\http\HttpController;

/**
 * 示例，首页
 * Class indexController
 * @package app\http\web
 */
class indexController extends HttpController{

    /**
     *
     * @param mixed ...$args
     * @return mixed|string
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        return "index page";
    }
}