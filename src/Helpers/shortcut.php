<?php
/**
 * Create By Hunter
 * 2019-08-16 01:14:02
 */

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
 * @return null
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
        if(\Small\Http\Request::isAjaxMethod()){
            $response->withJson([
                "error"     => 1,
                "message"   => $message
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
 * @param string|null $default
 * @param string|null $message
 * @return null
 */
function getUrlQuery(string $name, string $default = null, string $message = null){
    $value = \Small\Http\Request::get($name, $default);
    return parseHttpData($value, $message);
}
function getQueryString(string $name, string $default = null, string $message = null){
    return getUrlQuery($name, $default, $message);
}

/**
 * 获取POST数据
 * @param string $name
 * @param string|null $default
 * @param string|null $message
 * @return null
 */
function getPostData(string $name, string $default = null, string $message = null){
    $value = \Small\Http\Request::post($name, $default);
    return parseHttpData($value, $message);
}

/**
 * 向浏览器端返回数据
 * @param int $error
 * @param string $message
 * @param array $data
 */
function response($error=1, $message='', $data=[]){
    $data = is_array($error) || is_object($error) ? $error : (is_array($message) || is_object($message) ? $message : $data);
    $message = is_string($error) ? $error : (is_array($message) || is_object($message) ? null : $message);
    $error = is_array($error) || is_object($error) ? 0 : (is_string($error) ? 1 : ($error==1 || $error==0 ? $error : $error));
    //检测是Ajax访问，还是正常的GET,POST，如果是Ajax，使用json输出，如果是正常的GET,POST，则使用页面结果展示输出
    $json = [
        "error"     => $error,
        "message"   => $message,
        "data"      => is_string($data) ? ['callback_url'=>$data] : $data
    ];
    if($response = \Small\App::getContext("HttpResponse")){
        //从全局获取
    }else{
        //获取不到，新创建一个
        $response = new \Small\Http\HttpResponse();
    }
    if(\Small\Http\Request::isAjaxMethod()){
        $response->withJson($json)->send();
    }else{
        $response->withText($json)->send();
    }
}

/**
 * 重定向
 * @param string $url
 */
function redirect(string $url){
    if($response = \Small\App::getContext("HttpResponse")){
        $response->redirect($url);
    }
}