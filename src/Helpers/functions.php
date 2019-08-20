<?php

if(!function_exists("getIP")){

    /**
     * 获取 IP
     * @return array|false|null|string
     */
    function getIP()
    {
        static $realIp = NULL;
        if ($realIp !== NULL){
            return $realIp;
        }
        if (isset($_SERVER)){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                /* 取X-Forwarded-For中第x个非unknown的有效IP字符? */
                foreach ($arr as $ip)
                {
                    $ip = trim($ip);
                    if ($ip != 'unknown')
                    {
                        $realIp = $ip;
                        break;
                    }
                }
            }
            elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
                $realIp = $_SERVER['HTTP_CLIENT_IP'];
            }
            else{
                if (isset($_SERVER['REMOTE_ADDR'])){
                    $realIp = $_SERVER['REMOTE_ADDR'];
                }
                else{
                    $realIp = '0.0.0.0';
                }
            }
        }
        else{
            if (getenv('HTTP_X_FORWARDED_FOR')){
                $realIp = getenv('HTTP_X_FORWARDED_FOR');
            }
            elseif (getenv('HTTP_CLIENT_IP')){
                $realIp = getenv('HTTP_CLIENT_IP');
            }
            else{
                $realIp = getenv('REMOTE_ADDR');
            }
        }
        preg_match("/[\d\.]{7,15}/", $realIp, $onlineIp);
        $realIp = ! empty($onlineIp[0]) ? $onlineIp[0] : '0.0.0.0';
        return $realIp;
    }
}

if(!function_exists("server")){
    /**
     * 获取 config/server.php 的配置
     * @param string $path
     * @return mixed
     */
    function server(string $path){
        return \Small\Config::get("server.{$path}");
    }
}

if(!function_exists('config')){
    /**
     * 获取配置
     * @param string $key
     * @return array|null|string
     */
    function config(string $key){
        return \Small\Config::get($key);
    }
}

/**
 * 获取路由地址
 * @param $name
 * @return false|int|string
 */
function routPath($name){
    $list = server("route.http.list");
    if($path = array_search($name, $list)){
        return $path;
    }
    return "";
}

if(!function_exists('bytesToSize')){

    /**
     * 字节换算
     * @param int $bytes
     * @return string
     */
    function bytesToSize(int $bytes) : string{
        $sizes = ['Bytes', 'KB', 'MB'];
        if ($bytes == 0) return 'n/a';
        $i = intval(floor(log($bytes) / log(1024)));
        return sprintf("%2.f", ($bytes / pow(1024, $i))).' '.$sizes[$i];
    }
}

if(!function_exists("lang")){
    /**
     * 获取语言
     * @param string $key
     * @param string $language
     * @return string
     */
    function lang($key, $language = null){
        return \Small\Lang::getValue($key, $language);
    }
}



if(!function_exists('getVerifyCode')){
    /**
     * 获取验证码
     * @param string $name
     * @return string
     */
    function getVerifyCode($name='verify_code'){
        return strtolower(\Small\Http\Request::getSession($name));
    }

    /**
     * 销毁验证码
     * @param string $name
     */
    function resetVerifyCode($name='verify_code'){
        \Small\Http\Request::dropSession($name);
    }
}

/**
 * 处理静态文件地址
 * @param $val
 * @return string
 */
function attachmentUrlRebuild($val){
    $global = \Small\Config::get("domain");
    $val = !empty($val) ? (strripos($val, "http")===0 ? $val : ($global["attachment_url"].(substr($val,0,1)=="/" ? $val : ("/".$val)))) : null;
    return $val;
}

/**
 * 资源地址
 * @param $val
 * @return string|null
 */
function assetsUrl($val)
{
    $global = \Small\Config::get("domain");
    $val = !empty($val) ? (strripos($val, "http")===0 ? $val : ($global["assets_url"].(substr($val,0,1)=="/" ? $val : ("/".$val)))) : null;
    return $val;
}

if(!function_exists('url')){
    /**
     * 转换为URL地址
     * @param string$params
     * @return string
     */
    function url(string $params) : string{
        $params = stripos($params, "/")===0 ? substr($params, 1) : $params;
        $query = null;
        if(stripos($params, "?")>0){
            $url = substr($params, 0, stripos($params, "?"));
            $query = strchr($params, "?");
        }else{
            $url = $params;
        }
        if(stripos($url, ":")===0){
            $url = "admin".substr($url, 1);
        }
        $array = explode("/", $url);
        $domain = \Small\Config::get("domain");
        $scheme = $domain["scheme"];
        $domain = $domain['route'];
        $name = $array[0];
        if($domain && isset($domain[$name])){
            $array[0] = stripos($domain[$name], "http")!==0 ? ($scheme."://".$domain[$name]) : $domain[$name];
            return join("/", $array).$query;
        }else{
            $nameList = \Small\Config::get("server.route.http.list");
            if(in_array($name, array_values($nameList))){
                $array[0] = array_search($name, $nameList);
            }
            if($array[0] == \Small\Config::get("server.route.http.default")){
                unset($array[0]);
            }
            return "/".join("/", $array).$query;
        }
    }
}

if(!function_exists('urlRebuild')){
    /**
     * @param string $url     传入地址
     * @param array $params  传入参数
     * @param string $key
     * @param null $value
     * @return string
     */
    function urlRebuild(string $url, array $params, $key, $value=null) : string{
        $key_array = is_array($key) ? $key : [$key];
        $val_array = is_array($value) ? $value : [$value];
        $str = [];
        foreach ($key_array as $k=>$key){
            $_value = $val_array[$k] ?? null;
            if($_value===null && isset($params[$key])){
                unset($params[$key]);
            }elseif($_value!==null){
                $params[$key] = $_value;
            }
        }
        foreach ($params as $k=>$val){
            $str[] = "{$k}={$val}";
        }
        return url($url.(!empty($str) ? (stripos($url, "?") ? "" : "?").join("&", $str) : ""));
    }
}

/**
 * 生成地址参数
 * @param $array
 * @return string
 */
function makeUrlQuery(array $array) : string{
    $string = [];
    foreach ($array as $key=>$val){
        $string[] = "{$key}=".urlencode($val);
    }
    return join("&", $string);
}

/**
 * datetime 转 int
 * @param $datetime
 * @return false|int
 */
function datetime2time(string $datetime) : int{
    return strtotime($datetime);
}

/**
 * int 转 datetime
 * @param $time
 * @return false|string
 */
function time2datetime(int $time) : string{
    return date("Y-m-d H:i:s", $time);
}

if(!function_exists('curl_get')){
    /**
     * get方式获取网页数据
     * @param $url
     * @param array $data
     * @param string $type
     * @param null $ssl
     * @return mixed
     */
    function curl_get($url, $data=[], $type="get", $ssl=null){
        $ch = curl_init($url);
        curl_setopt( $ch , CURLOPT_TIMEOUT , 10 );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , TRUE );
        //https 请求
        if ( strlen( $url ) > 5 && strtolower( substr( $url , 0 , 5 ) ) == 'https' ){
            curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , FALSE );
            curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , FALSE );
            if(!is_null($ssl)){
                //加证书
                curl_setopt( $ch, CURLOPT_SSLCERTTYPE,$ssl['type']);
                curl_setopt( $ch, CURLOPT_SSLCERT, $ssl['cert']);
                curl_setopt( $ch, CURLOPT_SSLKEY, $ssl['key']);
            }
        }
        if($type=="get"){
            curl_setopt( $ch , CURLOPT_FAILONERROR , FALSE );
            $url_params = [];
            if(!empty($data)){
                foreach($data as $key=>$d){
                    $url_params[] = "{$key}=".urlencode($d);
                }
            }
            if(!empty($url_params)){
                $url .= (stripos($url, "?") ? "" : "?").join("&",$url_params);
            }
        }else{
            curl_setopt( $ch , CURLOPT_POST , TRUE );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , is_array($data) ? http_build_query($data) : $data);
        }
        curl_setopt( $ch , CURLOPT_URL , $url );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * post方式获取网页数据
     * @param $url
     * @param array $data
     * @return mixed
     */
    function curl_post($url, $data=[]){
        return curl_get($url, $data, "post");
    }
}

/**
 * 获取百度编辑器控件
 * @param string $field
 * @param string $html
 * @param bool $small
 * @param string $width
 * @param string $height
 * @return string
 */
function getUE($field, $html, $small=false, $width="100%", $height="400px") : string {
    $html = \Small\Util\Str::htmlDecode(\Small\Util\Str::htmlDecode($html));
    $editor_html = array();
    $editor_html[] = "<textarea name=\"{$field}\" id=\"{$field}\" style=\"width:{$width};height:300px;\" height=\"{$height}\" width=\"{$width}\">{$html}</textarea>";
    $domain = config("domain");
    if(!isset($GLOBALS["include_ue"])){
        $editor_html[] = '<script type="text/javascript">window.UEDITOR_HOME_URL = "'.$domain['assets_url'].'/third-party/ueditor1.3.5/";</script>';
        $editor_html[] = '<script type="text/javascript" src="'.$domain['assets_url'].'/third-party/ueditor1.3.5/ueditor.config.js?v=3.0"></script>';
        $editor_html[] = '<script type="text/javascript" src="'.$domain['assets_url'].'/third-party/ueditor1.3.5/ueditor.all.min.js"></script>';
        $editor_html[] = '<script type="text/javascript" src="'.$domain['assets_url'].'/third-party/ueditor1.3.5/lang/zh-cn/zh-cn.js"></script>';
        $GLOBALS["include_ue"] = true;
    }
    if($small){
        $editor_html[] = '<script type="text/javascript">window.UEDITOR_CONFIG.toolbars = [["undo","redo","bold","italic","underline","forecolor","fontsize","justifyleft","justifycenter","justifyright","justifyjustify"]];</script>';
    }
    $editor_html[] = "<script type='text/javascript'>UE.getEditor('{$field}')</script> ";
    return join("\r\n",$editor_html);
}

if(!function_exists('readonly')){
    /**
     * @param string $name
     * @return mixed|null|array
     */
    function readonly(string $name){
        $dir = \Small\Config::get("define.configs")."/readonly/";
        if(file_exists($dir."{$name}.php")){
            return include $dir."{$name}.php";
        }
        return null;
    }
}
