<?php
namespace Small\Http;

use Small\App;
use Small\View\View;

/**
 * HTTP控制器基类
 * Class HttpController
 * @package Small\Http
 */
class HttpController {

    /**
     * http response
     * @var HttpResponse
     */
    public $response = null;

    /**
     * 模板
     * @var View
     */
    public $view = null;

    /**
     * 模板
     * @var null|string
     */
    public $template = null;

    /**
     * 初始化控制器
     * HttpController constructor.
     */
    public function __construct()
    {
        $this->response = App::getContext("HttpResponse");
        $this->view = App::getContext("View");
        App::setContext("HttpController", $this);
    }

    /**
     * 绑定模板变量
     * @param $name
     * @param $value
     */
    public function assign($name, $value){
        assign($name, $value);
    }

    /**
     * 输出消息结果
     * @param int $error
     * @param string $message
     * @param array $data
     */
    public function response($error=1, $message='', $data=[]){
        $json = parseResponseData($error, $message, $data);
        if($this->isAjaxMethod()){
            $this->response->withJson($json)->send();
        }else{
            $this->response->withText($json)->send();
        }
    }

    /**
     * 检测是不是AJAX
     * @return bool
     */
    public function isAjaxMethod(){
        return Request::isAjaxMethod();
    }

    /**
     * 获取GET参数值
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed|string
     */
    public function getQueryString(string $name, string $default = null, string $message = null){
        return getUrlQuery($name, $default, $message);
    }

    /**
     * 获取POST参数值
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed
     */
    public function getPostData(string $name, string $default = null, string $message = null){
        return getPostData($name, $default, $message);
    }

    /**
     * 重定向
     * @param string $route
     */
    public function redirect(string $route){
        redirect($route);
    }
}