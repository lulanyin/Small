<?php
namespace Small\lib\util;

use Small\Config;

class Log {
    public static function log($content, $file_name='log'){
        if(is_string($content)){
            $path = Config::get("define.logs");
            if(!is_dir($path)){
                if(!mkdir($path, 0777, true)){
                    return;
                }
            }
            $f = fopen($path."/{$file_name}.txt", 'a');
            fwrite($f, date("Y/m/d H:i:s")."\r\n".$content."\r\n");
            fclose($f);
        }
    }
}