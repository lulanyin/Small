<?php
namespace Small\http;

use Small\IHttpController;
use Small\lib\util\Request;
use Small\lib\httpMessage\Response;
use Small\lib\view\View;
use Small\model\models\VisitLogModel;

abstract class HttpController implements IHttpController {

    /**
     * http response
     * @var Response
     */
    public $response;

    /**
     * 模板
     * @var View
     */
    public $view;

    /**
     * 登录账号资料
     * @var array
     */
    public $user = [
        "uid"       => 0,
        "group_id"  => 0,
        "nickname"  => '',
        "username"  => '',
        "email"     => '',
        "phone"     => '',
        "level"     => -1,
        "group_type"=> ''
    ];

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
     * @return mixed
     */
    abstract public function index(...$args);

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
     * @param $error
     * @param $message
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
        VisitLogModel::saveLog('response');
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
     * @param $name
     * @param string $default
     * @param string $message
     * @return mixed
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
     * @param $name
     * @param string $default
     * @param string $message
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

    public function getCookie(string $name, string $default = null)
    {
        // TODO: Implement getCookie() method.
        return Request::getCookie($name, $default);
    }
}