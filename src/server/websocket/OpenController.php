<?php
namespace Small\server\websocket;

use Small\server\ServerController;

/**
 * 新连接
 * Class openController
 * @package Small\server\controller
 */
class OpenController extends ServerController {

    /**
     *
     * @param mixed ...$args
     */
    public function index(...$args){

        //防继承时传入的 parent::index($args)
        $args = count($args)==1 && is_array($args[0]) ? $args[0] : $args;

        if(server('debug')){
            echo "[{$this->fd}] : 正在连接...".PHP_EOL;
        }
    }

}