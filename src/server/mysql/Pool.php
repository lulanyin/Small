<?php
namespace Small\server\mysql;

use Small\Config;
use Small\lib\util\Arr;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\MySQL;

class Pool {

    const READ = 'read';
    const WRITE = 'write';

    private $length = 5;

    /**
     * @var Channel
     */
    private $readPool;

    /**
     * @var Channel
     */
    private $writePool;

    /**
     * 请在协程中初始化，否则无法使用
     * 初始化连接池
     * Pool constructor.
     * @param int $size 每个 Worker 最多可使用的连接数据
     */
    public function __construct($size = 5)
    {
        $this->length = $size;
        $this->putConnect();
    }

    public function putConnect(){
        $this->readPool = new Channel($this->length);
        $this->writePool = new Channel($this->length);
        for($i = 0; $i < $this->length; $i ++){
            $this->put($this->connect(Pool::READ), Pool::READ);
            $this->put($this->connect(Pool::WRITE), Pool::WRITE);
        }
    }

    /**
     * 回收连接
     * @param $mysql
     * @param $type
     */
    public function put($mysql, $type = Pool::READ){
        if($type==Pool::READ){
            $this->readPool->push($mysql);
        }else{
            $this->writePool->push($mysql);
        }
    }

    /**
     * 获取连接
     * @param string $type
     * @return bool|MySQL
     */
    public function get($type = Pool::READ){
        $mysql = $type==Pool::READ ? $this->readPool->pop(5) : $this->writePool->pop(5);
        if($mysql == null){
            return null;
        }elseif(!$mysql->connected){
            return $this->connect($type);
        }
        return $mysql;
    }

    /**
     * @param string $type
     * @return null|MySQL
     */
    private function connect($type = Pool::READ){
        $config = Config::get("private.mysql");
        $name = $type==Pool::READ ? Pool::READ : Pool::WRITE;
        if(isset($config[$name])){
            $setting = $config[$name];
        }else{
            $setting = isset($config["default"]) ? $config['default'] : null;
        }
        if(!is_array($setting)){
            return null;
        }
        //配置
        $verify_config = [
            'host'      => Arr::get($setting, "host", "127.0.0.1"),
            'port'      => Arr::get($setting, "port", 3306),
            'user'      => Arr::get($setting, "user"),
            'password'  => Arr::get($setting, "password"),
            'database'  => Arr::get($setting, "database"),
            "charset"   => Arr::get($setting, "charset", "utf8"),
            'timeout'   => Arr::get($setting, "timeout", 2),
            'fetch_mode'=> true
        ];
        $mysql = new MySQL();
        //连接
        $res = $mysql->connect($verify_config);
        if(!$res){
            //连接未成功
            echo '[Mysql] : 数据库未连接...'.PHP_EOL;
        }
        return $res==false ? null : $mysql;
    }


    /**
     * @var Pool
     */
    public static $MysqlPool = null;

    /**
     * 获取连接池
     * @param string $type
     * @return MySQL
     */
    public static function getPool($type = Pool::READ){
        if(null == self::$MysqlPool){
            self::init();
        }
        return self::$MysqlPool->get($type);
    }

    /**
     * 释放回去
     * @param MySQL $mysql
     * @param string $type
     */
    public static function putPool(MySQL $mysql, $type = Pool::READ){
        if(null !== self::$MysqlPool){
            self::$MysqlPool->put($mysql, $type);
        }
    }

    /**
     * 初始化
     */
    public static function init(){
        if(null == self::$MysqlPool){
            $size = server("server.pool_size");
            $size = is_numeric($size) && $size>0 ? intval($size) : 5;
            self::$MysqlPool = new Pool($size);
        }
    }
}