<?php
namespace Small\server\http;

use Small\server\ServerController;

/**
 * HTTP Request Base Controller
 * Class RequestController
 * @package Small\server\controller
 */
class RequestController extends ServerController {

    /**
     * @var
     */
    public $response;

    /**
     * 默认处理路由方式
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        if(isset($args[0]) && isset($args[1])){
            if($args[0] instanceof \swoole_http_request && $args[1] instanceof \swoole_http_response){
                //处理路由
                $this->request = $args[0];

            }
        }
    }

    public function response($event = null, $error = 1, $message = '', $data = [])
    {

    }
}