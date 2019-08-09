<?php
namespace Small\DB;

use Small\Config;

class Redis{

    /**
     * @var Cache\driver\Redis
     */
    private static $redis = null;

    /**
     * @param $int
     * @return Cache\driver\Redis
     */
    public static function init($int = 0){
        if(self::$redis === null){
            self::$redis = self::newRedis($int);
        }
        return self::$redis;
    }

    public static function newRedis($int = 0){
        $config = Config::get("private.redis");
        $config['select'] = $int;
        return new Cache\driver\Redis($config);
    }
}