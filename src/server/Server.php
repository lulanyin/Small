<?php
namespace Small\server;

use Small\server\http\RequestController;
use Small\Config;
use Small\IServer;
use Small\lib\cache\Cache;

/**
 *
 * Class Server
 * @package Small\server
 */
class Server implements IServer {

    /**
     * 服务启动监听的IP
     * @var string
     */
    private $host = "0.0.0.0";

    /**
     * 监听端口
     * @var int
     */
    private $port = 9600;

    /**
     * 服务对象
     * @var \swoole_websocket_server
     */
    private $ws;

    /**
     * server constructor.
     */
    public function __construct()
    {
        $set = Config::get("server.server");
        $this->host = $set['host'] ?? $this->host;
        $this->port = $set['port'] ?? $this->port;
        $this->ws = new \swoole_websocket_server($this->host, $this->port);
        //配置
        $this->ws->set($set['setting']);

        //任务启动时
        $this->ws->on("WorkerStart", [$this, "workerStart"]);

        //任务启动时
        $this->ws->on("workerExit", [$this, "workerExit"]);

        //新客户端连接
        $this->ws->on("open", [$this, "open"]);

        //客户端发来消息
        $this->ws->on("message", [$this, "message"]);

        //HTTP请求
        $this->ws->on("request", [$this, "request"]);

        //客户端关闭
        $this->ws->on("close", [$this, "close"]);

        //异步任务投递
        $this->ws->on("task", [$this, "task"]);

        //异步任务完成
        $this->ws->on("finish", [$this, "finish"]);

    }

    /**
     * 服务启动，此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @param \swoole_websocket_server $server
     * @param int $worker_id
     */
    public function workerStart(\swoole_websocket_server $server, int $worker_id){
        $set = server("server");
        if(isset($set['start'])){
            $ctrl = $set['home'].$set['start']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->index($worker_id);
            }
        }
    }



    /**
     * 客户端连接成功
     * @param \swoole_websocket_server $server
     * @param \swoole_http_request $request
     */
    public function open(\swoole_websocket_server $server, \swoole_http_request $request){
        //连接，使用路由处理
        $set = server("server");
        if(isset($set['open'])){
            $ctrl = $set['home'].$set['open']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->request = $request;
                $ctrl->fd = $request->fd;
                $ctrl->index();
            }
        }
    }

    /**
     * 客户端发来消息
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function message(\swoole_websocket_server $server, \swoole_websocket_frame $frame){
        //做一个路由
        $set = server("server");
        if(isset($set['message'])){
            $ctrl = $set['home'].$set['message']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->frame = $frame;
                $ctrl->fd = $frame->fd;
                $ctrl->getCacheUser();
                $ctrl->index();
            }
        }
    }

    /**
     * 客户端HTTP请求
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function request(\swoole_http_request $request, \swoole_http_response $response){
        //做一个路由
        $set = server("server");
        if(isset($set['request'])){
            $ctrl = $set['home'].$set['request']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl();
            if($ctrl instanceof RequestController){
                $ctrl->index($request, $response);
            }
        }
    }

    /**
     * 客户端连接关闭
     * @param \swoole_websocket_server $server
     * @param int $fd
     */
    public function close(\swoole_websocket_server $server, int $fd){
        $set = server("server");
        if(isset($set['close'])){
            $ctrl = $set['home'].$set['close']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->fd = $fd;
                $ctrl->getCacheUser();
                $ctrl->index();
            }
        }
    }

    /**
     * 异步任务投递
     * @param \swoole_websocket_server $server
     * @param $task_id
     * @param $reactor_id
     * @param $data
     */
    public function task(\swoole_websocket_server $server, $task_id, $reactor_id, $data){
        //echo $task_id . " : Task 开始" . PHP_EOL;
        $set = server("server");
        if(isset($set['task'])){
            $ctrl = $set['home'].$set['task']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->task_id = $task_id;
                $ctrl->reactor_id = $reactor_id;
                $ctrl->data = $data;
                $ctrl->getCacheUser();
                $ctrl->index();
            }
        }
    }

    /**
     * 异步任务完成
     * @param \swoole_websocket_server $server
     * @param $task_id
     * @param $data
     */
    public function finish(\swoole_websocket_server $server, $task_id, $data){
        //echo $task_id . " : Task 结束" . PHP_EOL;
        $set = server("server");
        if(isset($set['finish'])){
            $ctrl = $set['home'].$set['finish']."Controller";
            if(!class_exists($ctrl)){
                return;
            }
            $ctrl = new $ctrl($server);
            if($ctrl instanceof ServerController){
                $ctrl->task_id = $task_id;
                $ctrl->data = $data;
                $ctrl->getCacheUser();
                $ctrl->index();
            }
        }
    }



    /**
     * 实现接口
     */
    public function start()
    {
        // TODO: Implement start() method.
        //启动服务前，将所有保存的Redis数据清空
        Cache::connect(2);
        Cache::clear();
        $this->ws->start();
    }
}