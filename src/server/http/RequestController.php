<?php
namespace Small\server\http;

use Small\Config;
use Small\IHttpController;
use Small\lib\util\Arr;
use Small\lib\util\Str;
use Small\lib\view\View;
use Small\server\ServerController;

/**
 * HTTP Request Base Controller
 * Class RequestController
 * @package Small\server\controller
 */
class RequestController extends ServerController implements IHttpController {

    /**
     * @var View
     */
    public $view;

    /**
     * 模板
     * @var null
     */
    public $template = null;

    /**
     * @var \swoole_http_response
     */
    public $swoole_response;

    /**
     * @var Response
     */
    public $response;

    /**
     *
     * @var array
     */
    public $GET = [];

    /**
     *
     * @var array
     */
    public $POST = [];

    /**
     *
     * @var array
     */
    public $COOKIES = [];

    /**
     * 默认处理路由方式
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        //防继承时传入的 parent::index($args)
        $args = count($args)==1 && is_array($args[0]) ? $args[0] : $args;

        // TODO: Implement index() method.
        if(isset($args[0]) && isset($args[1])){
            if($args[0] instanceof \swoole_http_request && $args[1] instanceof \swoole_http_response){
                //GET, POST
                $this->GET = is_array($args[0]->get) ? $args[0]->get : [];
                $this->POST = is_array($args[0]->post) ? $args[0]->post : [];
                $this->COOKIES = is_array($args[0]->cookie) ? $args[0]->cookie : [];
                //处理路由
                $this->request = $args[0];
                $this->swoole_response = $args[1];
                if($this->request->server['request_uri'] == "/favicon.ico"){
                    (new Response())
                        ->setResponse($this->swoole_response)
                        ->withStatus(200)
                        ->send();
                }elseif($this->request->server['request_method'] == "OPTIONS"){
                    //OPTIONS 请求
                    (new Response())
                        ->setResponse($this->swoole_response)
                        ->processOptions()
                        ->send();
                }else{
                    //
                    $path = $this->request->server['request_uri'];
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
                    $host = $this->request->header['host'];
                    //路由配置
                    $httpRoute = server("server.http");
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
                    $prefix = server("server.http.home");
                    $className = $prefix.join("\\", array_slice($pathArray, 0, -1))."Controller";
                    if(!class_exists($className)){
                        $method = "index";
                        $className = $prefix.join("\\", $pathArray)."Controller";
                        if(!class_exists($className)){
                            $className = $prefix.join("\\", $pathArray)."\indexController";
                            if(!class_exists($className)){
                                $this->whitStatus(404, "class {$className} not exists!");
                                return;
                            }else{
                                $pathArray[] = "index";
                            }
                        }
                    }else{
                        $method = end($pathArray);
                        $pathArray = array_slice($pathArray, 0, -1);
                    }
                    $class = new $className();
                    if($class instanceof RequestController){
                        $class->swoole_response = $this->swoole_response;
                        $class->request = $this->request;
                        $class->fd = $this->request->fd;
                    }else{
                        $this->whitStatus(404,"{$className} not instanceof RequestController", $pathArray);
                        return;
                    }
                    if(!method_exists($class, $method)){
                        $this->whitStatus(404,"(new {$className}())->{$method}(); method not exists!", $pathArray);
                        return;
                    }
                    //
                    try{
                        $mr = new \ReflectionMethod($className, $method);
                        $modifierName = \Reflection::getModifierNames($mr->getModifiers());
                        //判断方法是不是公开的
                        if($modifierName[0]!="public"){
                            $this->whitStatus(404, "(new {$className}())->{$method}(); not public method!", $pathArray);
                            return;
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
                            $this->whitStatus(404, "(new {$className}())->{$method}(); can execute parent method!", $pathArray);
                            return;
                        }
                        //实现...
                        $response = new Response($class, $method, $pathArray);
                        $response->send();
                    }catch (\ReflectionException $e){
                        $this->whitStatus(502, "ReflectionException : ".$e->getMessage(), $pathArray);
                        return;
                    }
                }
            }else{
                $this->swoole_response->status(502);
            }
        }else{
            $this->swoole_response->status(502);
        }
    }

    /**
     * @param $event
     * @param $error
     * @param $message
     * @param $data
     * @return Response
     */
    public function response($event = null, $error = 1, $message = '', $data = [])
    {
        $data = is_array($message) ? $message : null;
        $message = is_string($error) ? $error : null;
        $error = $event;

        $data = is_array($error) || is_object($error) ? $error : (is_array($message) || is_object($message) ? $message : $data);
        $message = is_string($error) ? $error : (is_array($message) || is_object($message) ? null : $message);
        $error = is_array($error) || is_object($error) ? 0 : (is_string($error) ? 1 : ($error==1 || $error==0 ? $error : $error));
        //检测是Ajax访问，还是正常的GET,POST，如果是Ajax，使用json输出，如果是正常的GET,POST，则使用页面结果展示输出
        $json = [
            "error"     => $error,
            "message"   => $message,
            "data"      => is_string($data) ? ['callback_url'=>$data] : $data
        ];
        return $this->response->withJson($json);
    }

    /**
     * 向浏览器发送状态结果，一般用于非200状态结果，如果是调试中，会输出文本
     * @param $code
     * @param $message
     * @param $pathArray
     */
    private function whitStatus($code, $message, $pathArray = ['web', 'index', 'index']){
        $debug = server('debug');
        $response = new Response($this, 'index', $pathArray);
        if($debug){
            //输出消息
            $response->withJson([
                "status"    => $code,
                "message"   => $message
            ])->send();
        }else{
            //输出 404
            $response = new Response($this, 'index', $pathArray);
            $response->withStatus($code)->withContent($debug ? $message : '')->send();
        }
    }

    /**
     * 获取GET
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed|string
     */
    public function getQueryString(string $name, string $default = null, string $message = null)
    {
        // TODO: Implement getQueryString() method.
        $fn = null;
        if(stripos($name, ":")>0){
            list($fn, $name) = explode(":", $name, 2);
        }
        $value = Arr::get($this->GET, $name, $default);
        $value = empty($value) && !is_numeric($value) ? $default : $value;
        $value = $fn ? Str::filter($fn, $value) : $value;
        $need = !is_null($message);
        if($need && $value!="0" && (is_null($value) || empty($value))){
            $this->response($message);
        }
        $this->GET[$name] = $value;
        return $value;
    }

    /**
     * 获取POST
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed
     */
    public function getPostData(string $name, string $default = null, string $message = null)
    {
        // TODO: Implement getPostData() method.
        $fn = null;
        if(stripos($name, ":")>0){
            list($fn, $name) = explode(":", $name, 2);
        }
        $value = Arr::get($this->POST, $name, $default);
        $value = empty($value) && !is_numeric($value) ? $default : $value;
        $value = $fn ? Str::filter($fn, $value) : $value;
        $need = !is_null($message);
        if($need && $value!="0" && (is_null($value) || empty($value))){
            $this->response($message);
        }
        $this->POST[$name] = $value;
        return $value;
    }

    /**
     * 获取Cookie
     * @param string $name
     * @param string|null $default
     * @return mixed|string
     */
    public function getCookie(string $name, string $default = null)
    {
        // TODO: Implement getCookie() method.
        return $this->COOKIES[$name] ?? $default;
    }

    /**
     * 是不是AJAX请求
     * @return bool|mixed
     */
    public function isAjaxMethod()
    {
        // TODO: Implement isAjaxMethod() method.
        $http_x_requested_with = $this->request->header['http_x_requested_with'] ?? null;
        return $http_x_requested_with == "XMLHttpRequest";
    }

    /**
     * 跳转
     * @param $route
     * @return mixed|void
     */
    public function redirect(string $route)
    {
        // TODO: Implement redirect() method.
        $this->response->redirect($route);
    }
}