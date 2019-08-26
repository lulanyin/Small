<?php
namespace Small\DB;

use PDO;
use PDOException;
use Small\Config;

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

    /**
     * 数据库连接对象（静态对象，保证全局只使用一次连接）
     * @var Connection
     */
    private static $connection = null;

    /**
     * 获取数据库连接对象
     * @return Connection
     */
    public static function getConnection(){
        if(is_null(self::$connection)){
            $config = Config::get("private.mysql");
            self::$connection = new Connection($config);
        }
        return self::$connection;
    }

    /**
     *
     * @param string $type
     * @return PDO
     */
    public static function getPdo($type = 'read'){
        return self::getConnection()->getPdo($type);
    }

    /**
     * 执行SQL语句
     * @param $sql
     * @param array $params
     * @return int
     */
    public static function execute($sql, $params = []){
        $con = self::getConnection();
        $sql = trim($sql);
        $pdo = $con->getPdo(stripos($sql, "SELECT") === 0 ? "read" : "write");
        try{
            $stm = $pdo->prepare($sql);
            if($stm->execute($params)){
                return $stm->rowCount();
            }else{
                $code = intval($stm->errorCode());
                throw new PDOException($stm->errorInfo()[2]."<br>query : ".$stm->queryString."<br>code source : ".$code, intval($code));
            }
        }catch (PDOException $exception){
            $con->setError($exception->getCode(), $exception->errorInfo);
            return false;
        }
    }
}