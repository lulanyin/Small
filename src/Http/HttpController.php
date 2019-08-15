<?php
namespace Small\Http;

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
    public $response;

    /**
     * 模板
     * @var View
     */
    public $view;

    /**
     * 模板
     * @var null
     */
    public $template = null;

    /**
     * 初始化控制器
     * HttpController constructor.
     */
    public function __construct()
    {
        
    }

    /**
     * 必须存在的index方法，默认路由入口
     * @param mixed ...$args
     */
    public function index(...$args){

    }

    /**
     * 绑定模板变量
     * @param $name
     * @param $value
     * @return View
     */
    public function assign($name, $value){
        return $this->view->assign($name, $value);
    }

    /**
     * 输出消息结果
     * @param int $error
     * @param string $message
     * @param array $data
     */
    public function response($error=1, $message='', $data=[]){
        response($error, $message, $data);
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
        $this->response->redirect($route);
    }
}