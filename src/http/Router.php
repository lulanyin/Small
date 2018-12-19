<?php
namespace Small\http;

use Small\Config;
use Small\IServer;
use Small\lib\httpMessage\Response;

/**
 * HTTP Router 处理正常伪静态路径
 * Class Router
 * @package Small\http
 */
class Router implements IServer {

    /**
     * HTTP 控制器文件夹目录，所有的路由，都会加上此前缀去处理
     * @var string
     */
    private $home;

    /**
     * 路由地址
     * @var string
     */
    private $uri;

    /**
     * 初始化
     * Router constructor.
     * @param string $uri 传入的地址参数，由 $_SERVER['REQUEST_URI'] 或 $_SERVER['PATH_INFO']，也可以自定义
     */
    public function __construct(string $uri = null)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * 设置HTTP控制器文件夹
     * @param string $home
     * @return Router
     */
    public function setHomePath(string $home){
        $this->home = $home;
        return $this;
    }

    /**
     * 开始处理路由
     */
    public function start(){
        //谷歌浏览器发起的，多一次请求
        if($this->uri == '/favicon.ico'){
            //直接 header status 200
            (new Response())->withStatus(200)->send();
        }
        //OPTIONS请求
        if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
            (new Response())->processOptions();
        }

        //
        $path = $this->uri;
        //处理地址参数
        if(stripos($path, "?")>0){
            $path = substr($path, 0, stripos($path, "?"));
        }
        $path = str_replace("\\", "/", $path);
        $path = str_replace("//", "/", $path);
        $path = strripos($path, "/")===0 ? substr($path, 1) : $path;
        if(substr($path, 0, 1)=="/"){
            $path = substr($path, 1);
        }
        if(strrchr($path, "/")=="/"){
            $path = substr($path, 0, -1);
        }
        //如果包住有.html
        //判断.html的位置
        if(strrchr($path, ".html")==".html"){
            $path = substr($path, 0, -5);
        }
        $domain = Config::get("domain");
        $route_list = isset($domain['route']) ? $domain['route'] : [];
        $host = $_SERVER['HTTP_HOST'];
        //路由配置
        $httpRoute = Config::get("server.route.http");
        if(is_array($route_list) && ($route = array_search($host, $route_list))){
            $pathArray = !empty($path) ? explode("/", $path) : [
                "index", "index"
            ];
            //至少为2长度 [class, ...method]
            if(count($pathArray)<2){
                $pathArray = array_pad($pathArray, 2, "index");
            }
            //加入路由 [ route, class, ...method ]
            $pathArray = array_merge([$route], $pathArray);
        }else{
            //可用入口
            $listPath = array_keys($httpRoute["list"]);
            $pathArray = !empty($path) ? explode("/", $path) : [
                $httpRoute["default"], "index", "index"
            ];
            if(!in_array($pathArray[0], $listPath)){
                $pathArray = array_merge([$httpRoute["default"]], $pathArray);
            }
            //替换
            $pathArray[0] = $httpRoute["list"][$pathArray[0]];
            if(count($pathArray)<3){
                $pathArray = array_pad($pathArray, 3, "index");
            }
        }

        //处理类的执行
        $prefix = $this->home ?? Config::get("server.route.http.home");
        $className = $prefix.join("\\", array_slice($pathArray, 0, -1))."Controller";
        if(!class_exists($className)){
            $method = "index";
            $className = $prefix.join("\\", $pathArray)."Controller";
            if(!class_exists($className)){
                $className = $prefix.join("\\", $pathArray)."\indexController";
                if(!class_exists($className)){
                    $this->whitStatus(404, "class {$className} not exists!");
                }else{
                    $pathArray[] = "index";
                }
            }
        }else{
            $method = end($pathArray);
            $pathArray = array_slice($pathArray, 0, -1);
        }
        $class = new $className();
        if(!method_exists($class, $method)){
            $this->whitStatus(404,"(new {$className}())->{$method}(); method not exists!");
        }
        //
        try{
            $mr = new \ReflectionMethod($className, $method);
            $modifierName = \Reflection::getModifierNames($mr->getModifiers());
            //判断方法是不是公开的
            if($modifierName[0]!="public"){
                $this->whitStatus(404, "(new {$className}())->{$method}(); not public method!");
            }
            //反射类，获取该类的方法列表，然后过滤掉继承类的方法，方法入口仅给
            $reflection = new \ReflectionObject($class);
            $methodList = $reflection->getMethods();
            $enableMethodList = [];
            foreach ($methodList as $item){
                if($item->class==$className){
                    $enableMethodList[] = $item->name;
                }
            }
            if(!in_array($method, $enableMethodList)){
                $this->whitStatus(404, "(new {$className}())->{$method}(); can execute parent method!");
            }
            //实现...
            $response = new Response($class, $method, $pathArray);
            $response->send();
        }catch (\ReflectionException $e){
            $this->whitStatus(502, "ReflectionException : ".$e->getMessage());
        }

    }

    /**
     * 向浏览器发送状态结果，一般用于非200状态结果，如果是调试中，会输出文本
     * @param $code
     * @param $message
     */
    public function whitStatus($code, $message){
        $debug = server('debug');
        if($debug){
            //输出消息
            echo json_encode([
                "status"    => $code,
                "message"   => $message
            ]);
        }else{
            //输出 404
            $response = new Response();
            $response->withStatus($code)->withContent($debug ? $message : '')->send();
        }
        exit;
    }

}