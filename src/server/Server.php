<?php
namespace Small\server;

use Small\lib\util\File;
use Small\middleware\IMiddleWare;
use Small\server\http\RequestController;
use Small\Config;
use Small\IServer;
use Small\lib\cache\Cache;
use Small\server\mysql\Pool;
use Small\server\websocket\MessageController;

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

    private $autoReloadProcessor;

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

        //自动重载进程
        $this->autoReloadProcessor = new \swoole_process([$this, 'autoReload']);
        $this->ws->addProcess($this->autoReloadProcessor);
    }

    /**
     * 服务启动，此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @param \swoole_websocket_server $server
     * @param int $worker_id
     */
    public function workerStart(\swoole_websocket_server $server, int $worker_id){
        echo "# ".$worker_id." Start".PHP_EOL;
        //加一条线程，进行监听文件变动
        if($server->worker_id == 0){
            echo "add reload process".PHP_EOL;
            $this->autoReloadGo($server);
        }
        // 自定义
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
     * worker 进程退出
     * @param \swoole_websocket_server $server
     * @param int $worker_id
     */
    public function workerExit(\swoole_websocket_server $server, int $worker_id){
        echo "worker [{$worker_id}] : exit.".PHP_EOL;
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
        if (isset($set['message'])) {
            $ctrl = $set['home'] . $set['message'] . "Controller";
            if (class_exists($ctrl)) {
                $ctrl = new $ctrl($server);
                if ($ctrl instanceof ServerController) {
                    $ctrl->frame = $frame;
                    $ctrl->fd = $frame->fd;
                    $ctrl->getCacheUser();
                    $ctrl->index();
                } else {
                    $this->messageDefault($server, $frame);
                }
            } else {
                $this->messageDefault($server, $frame);
            }
        } else {
            $this->messageDefault($server, $frame);
        }
    }

    /**
     * 默认处理
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    private function messageDefault(\swoole_websocket_server $server, \swoole_websocket_frame $frame){
        $ctrl = new MessageController($server);
        $ctrl->frame = $frame;
        $ctrl->fd = $frame->fd;
        $ctrl->getCacheUser();
        $ctrl->index();
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
            if(class_exists($ctrl)){
                $ctrl = new $ctrl($this->ws);
                if($ctrl instanceof RequestController){
                    list($ctrl, $request, $response) = $this->processMiddleWare('request', $ctrl, $request, $response);
                    $ctrl->index($request, $response);
                }else{
                    $this->requestDefault($request, $response);
                }
            }else{
                $this->requestDefault($request, $response);
            }
        }else{
            $this->requestDefault($request, $response);
        }
    }

    /**
     * 默认处理
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    private function requestDefault(\swoole_http_request $request, \swoole_http_response $response){
        try{
            $ctrl = new RequestController($this->ws);
            list($ctrl, $request, $response) = $this->processMiddleWare('request', $ctrl, $request, $response);
            $ctrl->index($request, $response);
        }catch (\Exception $e){

        }
    }

    /**
     * @param string $type
     * @param ServerController $ctrl
     * @param \swoole_http_request|null $request
     * @param \swoole_http_response|null $response
     * @return array(ServerController, \swoole_http_request, \swoole_http_response)
     */
    private function processMiddleWare(string $type, ServerController $ctrl, \swoole_http_request $request = null, \swoole_http_response $response = null){
        //判断
        $set = server("server");
        if(isset($set['middleware'])){
            if(!empty($set['middleware'][$type])){
                $middlewares = $set['middleware'][$type];
                $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
                foreach ($middlewares as $middleware){
                    if(class_exists($middleware)){
                        return $this->updateCtrlMiddleWare($ctrl, $middleware, $request, $response);
                    }
                }
            }
        }
        return [$ctrl, $request, $response];
    }

    /**
     * @param ServerController $ctrl
     * @param $middleware
     * @param \swoole_http_request|null $request
     * @param \swoole_http_response|null $response
     * @return array(ServerController, \swoole_http_request, \swoole_http_response)
     */
    private function updateCtrlMiddleWare(ServerController $ctrl, $middleware, \swoole_http_request $request = null, \swoole_http_response $response = null){
        $mw = new $middleware();
        if($mw instanceof IMiddleWare){
            return $mw->process($ctrl, $request, $response);
        }
        return [$ctrl, $request, $response];
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

    /**
     * 自动重载进程回调（死循环）
     * @param \swoole_process $process
     */
    public function autoReload(\swoole_process $process){
        while (true){
            $version = Config::get("define.runtime")."/version.json";
            $home = server("server.home");
            $home = str_replace("\\", "/", $home);
            //仅执更新控制器 + 模板
            $controllersList = File::getAllFiles($home, "php");
            //特定文件夹
            $views = Config::get("define.views");
            $viewsList = File::getAllFiles($views, "html|php|tpl", null);
            $list = [];
            foreach ($controllersList as $item){
                $list[] = [
                    "md5" => $item['md5'],
                    "file"=> $item['path']
                ];
            }
            foreach ($viewsList as $item){
                $list[] = [
                    "md5" => $item['md5'],
                    "file"=> $item['path']
                ];
            }
            if(is_file($version)){
                $md5_old = md5_file($version);
                $md5_new = md5(json_encode($list));
                if($md5_old!=$md5_new){
                    echo "server restart ...\r\n";
                    //apc_clear_cache();
                    opcache_reset();
                    file_put_contents($version, json_encode($list));
                    $this->ws->reload();
                }
            }else{
                file_put_contents($version, json_encode($list));
            }
            sleep(5);
        }
    }

    /**
     * 每5秒自动去判断一次文件版本
     * @param \swoole_websocket_server $server
     */
    public function autoReloadGo(\swoole_websocket_server $server){
        $this->autoReloadProcessor->write('start');
        $server->after(5000, function () use ($server){
            $this->autoReloadGo($server);
        });
    }
}