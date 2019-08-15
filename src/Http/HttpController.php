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
        $data = is_array($error) || is_object($error) ? $error : (is_array($message) || is_object($message) ? $message : $data);
        $message = is_string($error) ? $error : (is_array($message) || is_object($message) ? null : $message);
        $error = is_array($error) || is_object($error) ? 0 : (is_string($error) ? 1 : ($error==1 || $error==0 ? $error : $error));
        //检测是Ajax访问，还是正常的GET,POST，如果是Ajax，使用json输出，如果是正常的GET,POST，则使用页面结果展示输出
        $json = [
            "error"     => $error,
            "message"   => $message,
            "data"      => is_string($data) ? ['callback_url'=>$data] : $data
        ];
        if($this->isAjaxMethod()){
            $this->response->withJson($json)->send();
        }else{
            //需要一个展示消息的模板
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
        $value = Request::get($name, $default);
        $need = !is_null($message);
        if($need && $value!="0" && (is_null($value) || empty($value))){
            $this->response($message);
        }
        return $value;
    }

    /**
     * 获取POST参数值
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed
     */
    public function getPostData(string $name, string $default = null, string $message = null){
        $value = Request::post($name, $default);
        $need = !is_null($message);
        if($need && $value!="0" && (is_null($value) || empty($value))){
            $this->response($message);
        }
        return $value;
    }

    /**
     * 重定向
     * @param string $route
     */
    public function redirect(string $route){
        $this->response->redirect($route);
    }
}