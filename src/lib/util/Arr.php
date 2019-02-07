<?php
namespace Small\lib\util;

class Arr{
    /**
     * 转换数组的值里的伪键
     * @param array $array
     * @param array $static
     * @return array
     */
    public static function parseValue(array $array, array $static = []) : array {
        foreach ($array as $ak=>&$item){
            if(is_array($item)){
                $item = self::parseValue($item, array_merge($static, $array));
            }elseif(!empty($item)){
                $replace = Str::getMidString($item, "{", "}");
                if(!empty($replace)){
                    foreach ($replace as $value){
                        if(stripos($value, "@")===0){
                            //从 static 变量里找
                            $item = str_replace("{".$value."}", self::get($static, substr($value, 1)), $item);
                        }else{
                            //从当前数组里找
                            $item = str_replace("{".$value."}", self::get($array, $value), $item);
                        }
                    }
                }
            }
        }
        return $array;
    }

    /**
     * 获取数组的键值
     * @param array $array
     * @param $path
     * @param null $default
     * @return string|array|mixed|null
     */
    public static function get(array $array, $path = null, $default=null){
        if(null == $path){
            return $array;
        }
        $keys = explode(".", $path);
        $here = $array;
        foreach ($keys as $key){
            if(isset($here[$key])){
                $here = is_object($here[$key]) ? (array)$here[$key] : $here[$key];
            }else{
                $here = null;
                break;
            }
        }
        return $here ?? $default;
    }

    /**
     * 设置或修改数组的某个键值
     * @param array $array
     * @param $path
     * @param $value
     * @return array
     */
    public static function set(array &$array, $path, $value) : array {
        if(null == $path){
            $array = $value;
            return $array;
        }
        $keys = explode(".", $path);
        $len = count($keys);
        foreach ($keys as $i=>$key){
            if($i<$len-1){
                //非最后一个KEY
                if(!isset($array[$key])){
                    $array[$key] = [];
                }
            }else{
                //最后一个KEY
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * 根据条件获取一个列表
     * @param $array
     * @param $where
     * @return array
     */
    public static function getList($array, $where){
        $arr = [];
        foreach ($array as $item){
            $bool = true;
            foreach ($where as $key=>$val){
                if(isset($item[$key])){
                    if($item[$key]!=$val){
                        $bool = false;
                    }
                }else{
                    $bool = false;
                }
            }
            if($bool){
                $arr[] = $item;
            }
        }
        return $arr;
    }

    /**
     * 根据条件获取第一行
     * @param $array
     * @param null $where
     * @return array|mixed
     */
    public static function getFirst($array, $where=null){
        if(is_null($where)){
            return reset($array);
        }else{
            $res = self::getList($array, $where);
            if(!empty($res)){
                return reset($res);
            }
            return [];
        }
    }

    /**
     * 移除数组的键值！
     * @param array $arr
     * @param $keys
     * @return array
     */
    public static function unsetKey(array $arr, $keys){
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $k){
            if(isset($arr[$k])){
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    /**
     * 检测数组中是否带有对应的键名
     * @param $array
     * @param $key
     * @return bool
     */
    public static function has($array, $key){
        if (empty($array) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * 替换
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    public static function replace($array, $key, $value){
        return self::set($array, $key, $value);
    }

    /**
     * 截断
     * @param $array
     * @param $offset
     * @param null $len
     * @param null $preserve_keys
     * @return array
     */
    public static function slice($array, $offset, $len=null, $preserve_keys=null){
        return array_slice($array, $offset, $len, $preserve_keys);
    }
}