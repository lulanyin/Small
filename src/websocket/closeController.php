<?php
namespace Small\websocket;

/**
 * 客户端断开连接
 * Class closeController
 * @package app\commend\server\src
 */
class closeController extends WebSocketController {

    /**
     * 客户端断开连接
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        //连接断开，直接删除
        $this->exit();
        if(server("debug")){
            echo "[{$this->fd}] : 断开连接".PHP_EOL;
        }
    }
}