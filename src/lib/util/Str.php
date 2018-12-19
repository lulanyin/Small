<?php
namespace Small\lib\util;
class Str{
    /**
     * 取得字符串中某边界字符之间的字符串
     * @param string $string
     * @param string $start
     * @param string $end
     * @return array
     */
    public static function getMidString(string $string, string $start, string $end) : array {
        $start_index = stripos($string, $start);
        if($start_index!==false && strlen($string)>$start_index){
            $string = substr($string, $start_index+1);
            $end_index = stripos($string, $end);
            if($end_index!==false){
                $result = [
                    substr($string, 0, $end_index)
                ];
                if(strlen($string)>$end_index+1){
                    $next = self::getMidString(substr($string, $end_index+1), $start, $end);
                    return empty($next) ? $result : array_merge($result, $next);
                }else{
                    return $result;
                }
            }
        }
        return [];
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 处理规则，暂只支持通配符*
     * @param string $rule
     * @return string
     */
    public static function parseRule($rule)
    {
        return str_replace('\\*', '.*', preg_quote($rule));
    }

    public static function randomString($length=6){
        $str = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $rs_str = '';
        $len = count($str)-1;
        for($i=0;$i<$length;$i++)
        {
            $rs_str .= $str[rand(0,$len)];
        }
        return $rs_str;
    }

    public static function randomWord($length = 6){
        $str = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $rs_str = '';
        $len = count($str)-1;
        for($i=0;$i<$length;$i++)
        {
            $rs_str .= $str[rand(0,$len)];
        }
        return $rs_str;
    }

    /**
     * 空格全部去掉
     * @param $string
     * @param $replace
     * @return mixed
     */
    public static function trim($string, $replace=""){
        return preg_replace("/\s+/",$replace,$string);
    }

    /**
     * 是否是邮箱地址
     * @param $string
     * @return int
     */
    public static function isEmail($string){
        return preg_match('/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*@([a-z0-9]+[\-]?[a-z0-9]+\.)+[a-z]{2,6}$/i', $string);
    }

    /**
     * 是否是中国的手机号码
     * @param $string
     * @return int
     */
    public static function isPhoneNumber($string){
        return preg_match("/^1[3|4|5|7|8|9]{1}[0-9]{9}$/",$string);
    }

    /**
     * 是否是数字、字母、下划线组合
     * @param $string
     * @return int
     */
    public static function isLNU($string){
        return preg_match("/^[a-z0-9][a-z0-9_]*[a-z0-9]$/i",$string);
    }
    /**
     * 是否是字母开头，数字、字母、下划线组合
     * @param $string
     * @return int
     */
    public static function isLNUS($string){
        return preg_match("/^[a-z][a-z0-9_]*[a-z0-9]$/i",$string);
    }

    /**
     * 是不是中文
     * @param $string
     * @return int
     */
    public static function isZhChart($string){
        return !preg_match("/^[a-z0-9_|\*\?,\.\{\}\(\)\&\%\$\#\@\!]*[a-z0-9_|\*\?,\.\{\}\(\)\&\%$\#\@\!]$/i",$string);
    }

    /**
     * 是否是数字、字母、下划线组合
     * @param $string
     * @return int
     */
    public static function isLettersOrNumbersOrUnderscores($string){
        return self::isLNU($string);
    }

    /**
     * 返回随机数字
     * @param int $length
     * @return string
     */
    public static function randomNumber($length=6){
        $str = str_split('0123456789');
        $rs_str = '';
        $len = count($str)-1;
        for($i=0;$i<$length;$i++)
        {
            $rs_str .= $str[rand($i==0 ? 1 : 0,$len)];
        }
        return $rs_str;
    }

    /**
     * HTML解压
     * @param $html
     * @param int $encodeType
     * @return string
     */
    public static function htmlDecode($html, $encodeType=ENT_QUOTES){
        return empty($html) ? "" : htmlspecialchars_decode($html, $encodeType);
    }

    /**
     * HTML压缩
     * @param $html
     * @param int $encodeType
     * @return string
     */
    public static function htmlEncode($html, $encodeType=ENT_QUOTES){
        if(get_magic_quotes_gpc()){
            $html = stripslashes($html);
        }
        if($html=="0"){
            return $html;
        }
        return empty($html) ? "" : htmlspecialchars($html, $encodeType);
    }

    /**
     * 字符串替换
     * @param $from
     * @param $to
     * @param $object
     * @return mixed
     */
    public static function replace($from, $to, $object){
        return str_replace($from, $to, $object);
    }

    /**
     * 清除非法字符串
     * @param $string
     * @return mixed
     */
    public static function cleanIllegalString($string){
        $string = self::replace("../", "", $string);
        $string = self::replace("./", "", $string);
        $string = self::replace("./", "", $string);
        return $string;
    }

    /**
     * 从字符串中提取数字
     * @param $string
     * @param int $length
     * @return string
     */
    public static function getNumberString($string, $length=6){
        $arr = str_split($string);
        $str = [];
        foreach($arr as $c){
            if(is_numeric($c)){
                $str[] = $c;
            }
        }
        $str = join("", $str);
        $str = strlen($str)>$length ? substr($str, 0, $length) : $str;
        return empty($str) ? self::randomNumber($length) : $str;
    }

    /**
     * 根据字符串生成日期
     * @param $string
     * @return false|int
     */
    public static function makeTimeByString($string){
        if(empty($string)) return 0;
        $arr = explode(" ", $string);
        $Ymd = Arr::get($arr, 0, date("Y/m/d", time()));
        $His = Arr::get($arr, 1, "00:00:00");

        $YmdArr = explode("/", $Ymd);
        $Y = Arr::get($YmdArr, 0, date("Y"));
        $m = Arr::get($YmdArr, 1, date("m"));
        $d = Arr::get($YmdArr, 2, date("d"));

        $HisArr = explode(":", $His);
        $H = Arr::get($HisArr, 0, 0);
        $i = Arr::get($HisArr, 1, 0);
        $s = Arr::get($HisArr, 2, 0);

        return mktime($H, $i, $s, $m, $d, $Y);
    }

    /**
     * 检查身份证输入的有效性
     * @param $string
     * @param array $between
     * @return bool
     */
    public static function checkIdNumber($string, $between=[16, 70]){
        if(strlen($string)!=18){
            return false;
        }
        //前14位肯定是数字
        if(!is_numeric(substr($string, 0, 14))){
            return false;
        }
        //出生年份
        $year = substr($string, 6, 4);
        $nowYear = date("Y");
        if($year<$nowYear-$between[1] || $year>$nowYear-$between[0]){
            return false;
        }
        //出生月份
        $month = substr($string, 10, 2);
        if($month==0 || $month>12){
            return false;
        }
        //出生日
        $day = substr($string, 12, 2);
        switch ($month){
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                return $day>0 && $day<=31;
                break;
            case 2 :
                return $day>0 && $day<=($year%4==0 ? 29 : 28);
            default :
                return $day>0 && $day<=30;
                break;
        }
    }
    /**
     * 不管什么数字，都格式化成带2个小数的数字，小数四舍五入
     * @param $number
     * @param int $dec
     * @return string
     */
    public static function decimal($number, $dec=2){
        //获取小数点
        $pos = stripos($number, ".");
        $point = $pos>0 ? substr($number, $pos+1) : null;
        if(is_null($point)){
            return sprintf("%.{$dec}f", $number);
        }else{
            if(strlen($point)>$dec){
                return sprintf("%.{$dec}f",substr($number, 0, $pos).".".substr(round(floatval("0.".$point), $dec), 2, $dec));
            }
            return sprintf("%.{$dec}f", intval(substr($number, 0, $pos+1)) + floatval("0.".$point));
        }
    }

    /**
     * 截断数字
     * @param $number
     * @param int $cut_length
     * @return bool|string
     */
    public static function cutNumber($number, $cut_length=8){
        $number = strlen($number)>$cut_length ? substr($number,0, $cut_length) : $number;
        if(strchr($number, ".")=="."){
            $number = substr($number, 0, -1);
        }
        return $number;
    }


    /**
     * 隐藏手机号码
     * @param $phone
     * @return string
     */
    public static function hidePhoneNumber($phone){
        if(self::isPhoneNumber($phone)){
            return substr($phone, 0, 3)."****".substr($phone, -4);
        }
        return $phone;
    }

    /**
     * 过滤值
     * @param $fn
     * @param $value
     * @return int|mixed|null|string
     */
    public static function filter($fn, $value){
        switch ($fn){
            case "trim" :
                return is_string($value) ? self::trim($value) : $value;
                break;
            case "number" :
                return is_numeric($value) ? $value : null;
                break;
        }
        return $value;
    }

    public static function base64Decode($value){
        return stripos($value, "base64:")===0 ? base64_decode(substr($value, 7)) : $value;
    }

}