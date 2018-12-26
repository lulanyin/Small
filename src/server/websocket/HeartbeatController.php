<?php
namespace Small\server\controller;

/**
 * 接收心跳消息
 * Class heartbeatController
 * @package Small\server\controller
 */
class HeartbeatController extends ServerController{

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