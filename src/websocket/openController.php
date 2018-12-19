<?php
namespace Small\websocket;

/**
 * 处理连接
 * Class openController
 * @package app\commend\server\src
 */
class openController extends WebSocketController {

    /**
     *
     * @param mixed ...$args
     */
    public function index(...$args){
        if(server('debug')){
            echo "[{$this->fd}] : 正在连接...".PHP_EOL;
        }
    }

}