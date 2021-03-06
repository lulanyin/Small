<?php
/**
 * Create By Hunter
 * 2019-08-16 01:14:02
 */

use Small\App;

/**
 * 赋值变量到模板
 * @param $name
 * @param $value
 */
function assign($name, $value = null){
    if($view = \Small\App::getContext("View")){
        if(is_array($name)){
            $view->data = array_merge($view->data, $name);
        }else{
            $view->assign($name, $value);
        }
    }
}

/**
 * 处理HTTP的GET, POST数据
 * @param $value
 * @param null $message
 * @return mixed
 */
function parseHttpData($value, $message = null){
    $need = !is_null($message);
    if($need && $value!="0" && (is_null($value) || empty($value))){
        if($httpController = \Small\App::getContext("HttpController")){
            $httpController->response($message);
            return null;
        }elseif($response = \Small\App::getContext("HttpResponse")){
            //从全局获取
        }else{
            //新建
            $response = new \Small\Http\HttpResponse();
        }
        if(\Small\Http\Request::isAjaxMethod() || stripos($message, "json:") === 0  || App::getContext("ResponseType") == 'json'){
            $response->withJson([
                "error"     => 1,
                "message"   => stripos($message, "json:") === 0 ? substr($message, 5) : $message
            ])->send();
        }else{
            $response->withText($message)->send();
        }
    }
    return $value;
}

/**
 * 获取地址参数
 * @param string $name
 * @param $default
 * @param string|null $message
 * @return mixed
 */
function getUrlQuery(string $name, string $default = null, string $message = null){
    $value = \Small\Http\Request::get($name, $default);
    return parseHttpData($value, $message);
}
function getQueryString(string $name, string $default = null, string $message = null){
    return getUrlQuery($name, $default, $message);
}
function GET(string $name, string $default = null, string $message = null){
    return getUrlQuery($name, $default, $message);
}

/**
 * 获取POST数据
 * @param string $name
 * @param $default
 * @param string|null $message
 * @return mixed
 */
function getPostData(string $name, string $default = null, string $message = null){
    $value = \Small\Http\Request::post($name, $default);
    return parseHttpData($value, $message);
}
function POST(string $name, string $default = null, string $message = null){
    return getPostData($name, $default, $message);
}

/**
 * 处理为Response需要的数据
 * @param $error
 * @param $message
 * @param $data
 * @return array
 */
function parseResponseData($error = 1, $message = '', $data = []){
    $data = is_array($error) || is_object($error) ? $error : (is_array($message) || is_object($message) ? $message : $data);
    $message = is_string($error) ? $error : (is_array($message) || is_object($message) ? null : $message);
    $error = is_array($error) || is_object($error) ? 0 : (is_string($error) ? 1 : ($error==1 || $error==0 ? $error : $error));
    //检测是Ajax访问，还是正常的GET,POST，如果是Ajax，使用json输出，如果是正常的GET,POST，则使用页面结果展示输出
    $json = [
        "error"     => $error,
        "message"   => $message,
        "data"      => is_string($data) ? ['callback_url'=>$data] : $data
    ];
    return $json;
}

/**
 * 向浏览器端返回数据
 * @param $error
 * @param $message
 * @param $data
 */
function response($error = 1, $message = '', $data = []){
    if($httpController = App::getContext("HttpController")){
        $httpController->response($error, $message, $data);
    }else{
        $json = parseResponseData($error, $message, $data);
        if($response = \Small\App::getContext("HttpResponse")){
            //从全局获取
        }else{
            //获取不到，新创建一个
            $response = new \Small\Http\HttpResponse();
        }
        if(\Small\Http\Request::isAjaxMethod() || App::getContext("ResponseType") == 'json'){
            $response->withJson($json)->send();
        }else{
            $response->withText($json)->send();
        }
    }
}

/**
 * 重定向
 * @param string $url
 * @param int $status
 */
function redirect(string $url, $status = 301){
    if($response = \Small\App::getContext("HttpResponse")){
        $response->redirect($url, $status);
    }
}

/**
 * 检测是不是AJAX访问
 * @return bool
 */
function isAjaxMethod(){
    return \Small\Http\Request::isAjaxMethod();
}