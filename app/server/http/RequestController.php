<?php
namespace app\server\http;

/**
 * Class RequestController
 * @package app\server\http
 */
class RequestController extends \Small\server\http\RequestController{

    /**
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        //[0] => swoole_http_request,
        //[1] => swoole_http_response
        //使用父类的默认方式处理路由
        parent::index($args); // TODO: Change the autogenerated stub
    }
}