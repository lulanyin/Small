<?php
namespace Small\websocket\mysql;

use Swoole\Coroutine\MySQL;

class DB {

    public static function from($table, string $as = null, MySQL $mysql = null) : Query{
        $query = new Query($mysql);
        return $query->from($table, $as);
    }

    /**
     * 写入错误日志
     * @param string $text
     */
    public static function log(string $text){
        if(!empty($text)){
            $path = Config::get("define.logs");
            File::writeEnd($path, "db.log", "时间：".date("Y/m/d H:i:s", time())."\r\n  ".$text."\r\n");
        }
    }

    /**
     * 开始事务，返回数据库操作对象
     * @return Query
     */
    public static function begin(){
        $query = new Query();
        return $query->begin();
    }

    /**
     * 单纯获取一个操作对象
     * @param MySQL|null $mysql
     * @return Query
     */
    public static function getQuery(MySQL $mysql=null){
        return new Query($mysql);
    }

}