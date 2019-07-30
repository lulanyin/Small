<?php
namespace Small\DB;

/**
 *
 * Class DB
 * @package Small\DB
 */
class DB
{

    /**
     * DB::from('user') ...
     * @param $table
     * @param null $as
     * @return Query
     */
    public static function from($table, $as = null)
    {
        $query = new Query();
        return $query->from($table, $as);
    }

    /**
     * 记录日志，一般是记录错误信息
     * @param string $text 日志内容
     * @param bool $output 是否要输出给用户看
     */
    public static function log($text, $output=false){
        if(!empty($text)){
            $dir = defined('RUNTIME') ? RUNTIME."/db" : (defined('ROOT') ? ROOT."/runtime/db" : __DIR__."/../../runtime/db");
            if(!is_dir($dir)){
                if(!@mkdir($dir, 0777, true)){
                    echo "目录不可创建：{$dir}".PHP_EOL;
                }
            }
            $f = fopen($dir."/error.txt", "a");
            if($f){
                @fwrite($f, "时间：".date("Y/m/d H:i:s", time())."\r\n  ".$text."\r\n\r\n");
                @fclose($f);
            }
        }
        if($output){
            $text = str_replace("\r\n", "<br>", $text);
            echo $text.PHP_EOL;
        }
    }
}