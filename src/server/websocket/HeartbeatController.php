<?php
namespace Small\server\websocket;

use Small\server\ServerController;

/**
 * 接收心跳消息
 * Class heartbeatController
 * @package Small\server\controller
 */
class HeartbeatController extends ServerController {

    /**
     * 接收心跳消息
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        //防继承时传入的 parent::index($args)
        $args = count($args)==1 && is_array($args[0]) ? $args[0] : $args;

        // TODO: Implement index() method.

        $this->response('heartbeat', 0, 'Hi!');
    }
}