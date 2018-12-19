<?php
namespace app\websocket\chat;

use Small\websocket\WebSocketController;

class sendController extends WebSocketController{

    public function index(...$args)
    {
        // TODO: Implement index() method.

        $this->response('chat', 0,'hi');
    }
}