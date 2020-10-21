<?php
namespace Small\Http;

use Small\Annotation\Annotation;
use Small\Config;
use Small\App;

/**
 * HTTP响应数据处理
 * Class HttpResponse
 * @package Small\Http
 */
class HttpResponse{

    /**
     * cookies
     * @var array
     */
    private $cookies = [];

    /**
     * 编码
     * @var string
     */
    private $charset = "UTF-8";

    /**
     * header
     * @var array
     */
    private $headers = [];

    /**
     * 主体内容
     * @var null
     */
    private $content = null;

    /**
     * http状态码
     * @var int
     */
    private $status = 200;

    /**
     * http版本，1.1, 2.0
     * @var string
     */
    private $version = "1.1";

    /**
     * 控制器的路径
     * @var array
     */
    private $pathArray = [];

    /**
     * 初始化
     * HttpResponse constructor.
     * @param $controller
     * @param string $method
     * @param array $pathArray
     */
    public function __construct($controller = null, string $method = null, array $pathArray = null)
    {
        $this->init($controller, $method, $pathArray);
    }

    /**
     * 方便二次调用
     * @param null $controller
     * @param string|null $method
     * @param array|null $pathArray
     * @return HttpResponse
     */
    public function init($controller = null, string $method = null, array $pathArray = null){
        if(null!==$controller){
            $this->pathArray = $pathArray;
            //使用注解实现@Controller()
            $annotation = Config::get("server.route.annotation");
            $annotation = $annotation ?? false;
            $annotation_controller = null;
            if($annotation){
                $annotation_controller = Config::get("server.route.annotation_controller");
                $annotation_controller = !empty($annotation_controller) ? $annotation_controller : HttpController::class;
                if(!class_exists($annotation_controller)){
                    $annotation_controller = HttpController::class;
                }
            }
            //开始执行
            if(!empty($annotation_controller)){
                $instance = new $annotation_controller();
                Annotation::process($instance);
            }
            //处理注解，如果有After注解，会返回After列表
            $result = Annotation::process($controller, $method);
            if($result instanceof HttpResponse){
                $result->send();
            }elseif(is_string($result)){
                $this->withAddHeader("Content-Type", "text/plain")->withContent($result);
            }elseif(is_object($result) || is_array($result)){
                $this->withJson($result);
            }elseif(!empty($this->content)){
                //已经设置有内容
                $this->send();
            }else{
                if($view = App::getContext("View")){
                    $this->withAddHeader("Content-Type", "text/html")->withContent($view->fetch());
                }else{
                    $this->withText('no more content');
                }
            }
        }
        return $this;
    }

    /**
     * 设置header
     * @param $key
     * @param $value
     * @return HttpResponse
     */
    public function withHeader(string $key, $value){
        $this->headers[$key] = is_array($value) ? $value : [$value];
        return $this;
    }

    /**
     * 增加header
     * @param $key
     * @param $value
     * @return HttpResponse
     */
    public function withAddHeader(string $key, $value){
        if(!is_array($value)){
            $value = [$value];
        }
        if(isset($this->headers[$key])){
            $this->headers[$key] = array_merge($this->headers[$key], $value);
        }else{
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * 设置cookie
     * @param $name
     * @param $value
     * @param int $time
     * @return HttpResponse
     */
    public function withCookie(string $name, string $value, $time = 3600){
        $this->cookies[] = [
            "name"      => $name,
            "value"     => $value,
            "time"      => $time
        ];
        return $this;
    }

    /**
     * 移除cookie
     * @param $name
     * @return HttpResponse
     */
    public function withoutCookie(string $name){
        return $this->withCookie($name, null, -1);
    }

    /**
     * 往客户端输出状态
     * @param int $code
     * @return HttpResponse
     */
    public function withStatus(int $code){
        $this->status = $code;
        return $this;
    }

    /**
     * 输出JSON
     * @param $object
     * @return HttpResponse
     */
    public function withJson($object){
        return $this->withHeader("Content-Type", "application/json")->withContent($object);
    }

    /**
     * 设置content
     * @param $content
     * @return HttpResponse
     */
    public function withContent($content){
        $this->content = is_string($content) ? $content : (is_array($content) || is_object($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content);
        return $this;
    }

    /**
     * 输出文本
     * @param $text
     * @return HttpResponse
     */
    public function withText($text){
        return $this->withHeader("Content-Type", "text/plain")->withContent($text);
    }

    /**
     * 设置编码
     * @param string $charset
     * @return HttpResponse
     */
    public function setChar($charset = "utf-8"){
        $this->charset = $charset;
        return $this;
    }

    /**
     * 设置http版本
     * @param string $version
     * @return HttpResponse
     */
    public function version($version = "1.1"){
        $this->version = $version=="2.0" || $version==2 ? "2.0" : "1.1";
        return $this;
    }

    /**
     * 向客户端发送结果数据
     */
    public function send(){
        //处理cookies
        if(!empty($this->cookies)){
            foreach ($this->cookies as $cookie){
                Request::setCookie($cookie['name'], $cookie['value'], $cookie['time']);
            }
        }
        //加入跨域
        $path = $this->pathArray[0] ?? '';
        //查找是否配置有独立域名
        if(!empty($path)){
            $this->accessOriginProcess($path);
        }
        //加入编码
        $this->withAddHeader("Content-Type", "charset={$this->charset}");
        //处理headers
        if(!empty($this->headers)){
            foreach ($this->headers as $name=>$header){
                header($name.":".implode(";", $header), false, $this->status);
            }
        }
        //输出内容结果
        echo $this->content ?? '';
        //echo round(microtime(true) - START_SECONDS, 5);
        $this->exit();
    }

    /**
     * 处理跨域
     * @param $path
     * @return bool
     */
    public function accessOriginProcess($path){
        $domain = Config::get("domain");
        $routeDomain = $_SERVER["HTTP_HOST"];
        $host = $_SERVER['HTTP_ORIGIN'] ?? $routeDomain;
        if($host!=$routeDomain){
            //不同的域名，需要判断是否在白名单中，黑名单在HttpWorker已处理，黑名单的IP、域名是进不来这里的
            $access_origin = $domain['access_origin'];
            $checkWhiteList = true;
            if(isset($access_origin[$path])){
                if(is_array($access_origin[$path])){
                    if(in_array($host, $access_origin[$path])){
                        $this->agreeHost($host);
                        $checkWhiteList = false;
                    }else{
                        return false;
                    }
                }elseif($access_origin[$path] == "*"){
                    $this->agreeHost("*");
                    $checkWhiteList = false;
                }
            }
            $whitelist = $domain['whitelist'];
            if(!empty($whitelist) && $checkWhiteList){
                if(in_array($whitelist, $host)){
                    $this->agreeHost($host);
                }else{
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 允许跨域域名
     * @param $host
     * @return HttpResponse
     */
    public function accessOrigin($host){
        return $this->withHeader("Access-Control-Allow-Origin", $host);
    }

    /**
     * 允许跨域头部请求
     * @param $header
     * @return HttpResponse
     */
    public function accessHeader($header){
        return $this->withHeader("Access-Control-Allow-Headers", $header);
    }

    /**
     * 允许跨域方法
     * @param $methods
     * @return HttpResponse
     */
    public function accessMethods($methods){
        return $this->withHeader("Access-Control-Allow-Methods", $methods);
    }

    /**
     * 跨域绿色通道
     * @param $host
     * @return HttpResponse
     */
    public function agreeHost($host){
        return $this->accessOrigin($host)
            ->accessHeader("Content-Type")
            ->accessHeader("X-Requested-With")
            ->accessMethods("GET,PUT,DELETE,POST,OPTIONS");
    }

    /**
     * 直接输出状态码
     * @param int $code
     * @return HttpResponse
     */
    public function sendStatus(int $code){
        header("Content-Type:charset={$this->charset}", false, $code);
        return $this;
    }

    /**
     * 处理OPTIONS请求
     * @return HttpResponse
     */
    public function processOptions(){
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:GET,POST,OPTIONS');
        header("Access-Control-Allow-Headers:X-Requested-With, Content-Type, Access-Control-Allow-Origin, Access-Control-Allow-Headers, X-Requested-By, Access-Control-Allow-Methods");
        return $this;
    }

    /**
     * 重定向
     * @param string $route
     * @param int $status
     */
    public function redirect(string $route, $status = 301){
        $this->withStatus($status);
        if(stripos($route, "/")===0 || stripos($route, ":")===0){
            $route = url($route);
        }elseif(stripos($route, "http")!==0){
            $route = url("/".($this->pathArray[0] ?? "")."/".$route);
        }
        $this->withHeader("Location", $route)->send();
    }

    /**
     * 退出
     */
    public function exit(){
        exit();
    }
}