<?php
namespace app\server\websocket\chat;

use Small\server\ServerController;

class sendController extends ServerController {

    public function index(...$args)
    {
        // TODO: Implement index() method.

        $this->response('chat', 0,'hi');
    }
}