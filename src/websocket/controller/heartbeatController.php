<?php
namespace app\commend\server\src\controller;

use app\commend\server\src\WebSocketController;

/**
 * 接收心跳消息
 * Class heartbeatController
 * @package app\commend\server\src\controller
 */
class heartbeatController extends WebSocketController{

    /**
     * 接收心跳消息
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.

        $this->response('heartbeat', 0, 'Hi!');
    }
}