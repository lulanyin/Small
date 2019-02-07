<?php

/**
 * \Small\APP 类代理
 * Class App
 */
class App extends \Small\App{

}

/**
 * \Small\Config 类代理
 * Class Config
 */
class Config extends \Small\Config{

}

/**
 * 从Redis中读取业务配置
 * @param null $key
 * @return array|mixed|null|string
 */
function getBusiness($key = null){
    if($values = \Small\lib\cache\Cache::get('public_business')){
        return \Small\lib\util\Arr::get($values, $key);
    }else{
        $values = Config::get("public.business");
        \Small\lib\cache\Cache::set("public_business", $values);
        return \Small\lib\util\Arr::get($values, $key);
    }
}

/**
 * 更新Redis中的业务配置
 * @param array $values
 * @return bool
 */
function updateBusinessCache(array $values){
    return \Small\lib\cache\Cache::set("public_business", $values);
}