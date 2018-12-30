<?php
namespace Small\server\websocket;

use Small\server\ServerController;

/**
 * 处理消息
 * Class MessageController
 * @package Small\server\controller
 */
class MessageController extends ServerController {

    /**
     * 消息进入，处理路由
     * @param mixed ...$args
     */
    public function index(...$args)
    {
        //防继承时传入的 parent::index($args)
        $args = count($args)==1 && is_array($args[0]) ? $args[0] : $args;

        if(server('debug')){
            echo "[{$this->fd}] : 发来消息".PHP_EOL;
            echo $this->frame->data.PHP_EOL;
        }
        // TODO: Implement index() method.
        //处理数据原文
        //{
        //  event : 'chat.group.join',
        //  event : 'chat/group/join'
        //  data : {
        //      name : 'SD123456'
        //  }
        //}
        if($json = @json_decode($this->frame->data)){
            $event = $json->event ?? '';
            //
            $prefix = server("server.websocket.home");
            if($event == 'heartbeat'){
                $ctrl = server("server.heartbeat");
                if($ctrl && class_exists($prefix.$ctrl."Controller")){
                    $className = $prefix.$ctrl."Controller";
                    $controller = new $className();
                }else{
                    $controller = new HeartbeatController();
                }
                $controller->ws = $this->ws;
                $controller->data = $this->data;
                $controller->frame = $this->frame;
                $controller->user = $this->user;
                $controller->fd = $this->fd;
                $controller->event = $event;
                $controller->index();
                return;
            }
            $data = $json->data ?? [];
            $data = is_array($data) || is_object($data) ? (array)$data : [];
            //保存起来
            $this->data = $data;
            if(!empty($event)){
                //根据event来判断控制
                $event = str_replace("\\", "/", $event);
                $event = str_replace("/", ".", $event);
                $event = $this->cleanDot($event);
                $event = $this->replaceDot($event);
            }else{
                $event = 'index.index';
            }
            $eventArray = explode(".", $event);
            $eventArray = count($eventArray)<2 ? array_pad($eventArray, 2, "index") : $eventArray;
            $class = $prefix.join("\\", array_slice($eventArray, 0, -1))."Controller";
            if(!class_exists($class)){
                $class = $prefix.join("\\", $eventArray)."Controller";
                if(!class_exists($class)){
                    //不存在
                    $className = null;
                    $method = null;
                }else{
                    $method = "index";
                    $className = $class;
                }
            }else{
                $method = end($eventArray);
                $className = $class;
            }
            if(null !== $className){
                $controller = new $className();
                if(method_exists($controller, $method)){
                    try{
                        $mr = new \ReflectionMethod($className, $method);
                        $modifierName = \Reflection::getModifierNames($mr->getModifiers());
                        //判断方法是不是公开的
                        if($modifierName[0]=="public"){
                            //反射类，获取该类的方法列表，然后过滤掉继承类的方法，方法入口仅给
                            $reflection = new \ReflectionObject($controller);
                            $methodList = $reflection->getMethods();
                            $enableMethodList = [];
                            foreach ($methodList as $item){
                                if($item->class==$className){
                                    $enableMethodList[] = $item->name;
                                }
                            }
                            if(in_array($method, $enableMethodList)){
                                //可以使用
                                if($controller instanceof ServerController){
                                    $controller->ws = $this->ws;
                                    $controller->data = $this->data;
                                    $controller->frame = $this->frame;
                                    $controller->user = $this->user;
                                    $controller->fd = $this->fd;
                                    $controller->event = $event;
                                }
                                //执行方法
                                $controller->{$method}();
                            }else{
                                $this->response($event, "非内置方法，不处理");
                            }
                        }else{
                            $this->response($event, "私有方法，不开放");
                        }
                    }catch (\ReflectionException $exception){
                        $this->response($event, '请求处理失败');
                    }
                }else{
                    $this->response($event, '无效请求');
                }
            }else{
                $this->response($event, '未知请求');
            }
        }else{
            //不作任何处理
            $this->response('unknow', '未知数据格式');
        }
    }

    /**
     * 清除左右两边的点
     * @param $event
     * @return string
     */
    private function cleanDot(string $event) : string {
        if(stripos($event, ".")===0){
            $event = substr($event, 1);
        }
        if(strrchr($event, ".") == "."){
            $event = substr($event, -1);
        }
        if(stripos($event, ".")===0 || strrchr($event, ".") == "."){
            return $this->cleanDot($event);
        }
        return $event;
    }

    /**
     * 将双点换成单点
     * @param $event
     * @return string
     */
    private function replaceDot(string $event) : string {
        $event = str_replace("..", ".", $event);
        if(stripos($event, "..")!==false){
            return $this->replaceDot($event);
        }
        return $event;
    }
}