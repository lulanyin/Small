<?php
namespace Small\Http;

use Reflection;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use Small\App;
use Small\Config;
use Small\IServer;
use Small\View\View;

/**
 * HTTP Router 处理正常伪静态路径
 * Class Router
 * @package Small\Http
 */
class HttpRouter implements IServer {

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
     * @return HttpRouter
     */
    public function setHomePath(string $home){
        $this->home = $home;
        return $this;
    }

    /**
     * 开始处理路由
     */
    public function start(){
        //全局响应类
        $response = new HttpResponse();
        App::setContext("HttpResponse", $response);
        //OPTIONS请求
        if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
            $response->processOptions()->exit();
        }
        //正常访问资源
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
        //谷歌浏览器发起的，多一次请求
        if($path == 'favicon.ico'){
            //直接 header status 200
            $response->withStatus(200)->send();
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
            $pathLen = count($pathArray) + 1;
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
            $pathLen = count($pathArray);
            if(count($pathArray)<3){
                $pathArray = array_pad($pathArray, 3, "index");
            }
        }
        //如果设置了
        if(isset($httpRoute['response_type']) && isset($httpRoute['response_type'][$pathArray[0]])){
            App::setContext('ResponseType', $httpRoute['response_type'][$pathArray[0]]);
        }
        //处理类的执行
        $prefix = $this->home ?? Config::get("server.route.http.home");
        //控制器是否打开了使用注解类实现，未配置的控制器必须继承HttpController，配置了注解的，将使用注入方式
        $annotation = Config::get("server.route.annotation");
        $annotation = $annotation ?? false;
        $className = $prefix.join("\\", array_slice($pathArray, 0, -1)).($annotation ? "" : "Controller");
        if(!class_exists($className)){
            $method = "index";
            $className = $prefix.join("\\", $pathArray).($annotation ? "" : "Controller");
            if(!class_exists($className)){
                $className = $prefix.join("\\", $pathArray)."\index".($annotation ? "" : "Controller");
                if(!class_exists($className)){
                    $method = $pathLen<=2 ? $pathArray[count($pathArray)-2] : end($pathArray);
                    $pathArray = array_merge(array_slice($pathArray, 0, $pathLen<=2 ? -2 : -1), ["index"]);
                    $className = $prefix.join("\\", $pathArray).($annotation ? "" : "Controller");
                    if(!class_exists($className)) {
                        $this->whitStatus(404, "class {$className} not exists!");
                    }
                }else{
                    $pathArray[] = "index";
                }
            }
        }else{
            $method = end($pathArray);
            $pathArray = array_slice($pathArray, 0, -1);
        }
        //全局视图类
        $view = new View();
        App::setContext("View", $view);
        //开始创建
        $class = new $className();
        if(!method_exists($class, $method)){
            $this->whitStatus(404,"(new {$className}())->{$method}(); method not exists!");
        }
        //
        try{
            $mr = new ReflectionMethod($className, $method);
            $modifierName = Reflection::getModifierNames($mr->getModifiers());
            //判断方法是不是公开的
            if($modifierName[0]!="public"){
                $this->whitStatus(404, "(new {$className}())->{$method}(); not public method!");
            }
            //反射类，获取该类的方法列表，然后过滤掉继承类的方法，方法入口仅给
            $reflection = new ReflectionObject($class);
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
            //Response 处理 ...
            $view->init($class, $method, $pathArray);
            $response->init($class, $method, $pathArray)->send();
        }catch (ReflectionException $e){
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
            exit(json_encode([
                    "error"     => 1,
                    "status"    => $code,
                    "message"   => $message
                ]).PHP_EOL);
        }else{
            //输出 404
            App::getContext("HttpResponse")->withStatus($code)->withContent($debug ? $message : '')->send();
        }
    }

}