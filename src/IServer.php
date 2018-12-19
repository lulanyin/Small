<?php
namespace Small;

/**
 * 通用服务接口，实现 start
 * 用于 Http, WebSocket 服务启动时的快捷入口统一命名
 * Interface IServer
 * @package Small
 */
interface IServer {

    public function start();

}