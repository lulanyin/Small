<?php
namespace Small\server\websocket;

use Small\server\ServerController;

/**
 * 客户端断开连接
 * Class CloseController
 * @package Small\server\controller
 */
class CloseController extends ServerController {

    /**
     * 客户端断开连接
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        //防继承时传入的 parent::index($args)
        $args = count($args)==1 && is_array($args[0]) ? $args[0] : $args;

        // TODO: Implement index() method.
        //连接断开，直接删除
        $this->exit();
        if(server("debug")){
            echo "[{$this->fd}] : 断开连接".PHP_EOL;
        }
    }
}