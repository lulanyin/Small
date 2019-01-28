<?php
namespace Small\lib\cache;

use Small\Config;

class Redis{

    /**
     * @var \DB\Cache\driver\Redis|null
     */
    private static $redis = null;

    /**
     * @param $int
     * @return \DB\Cache\driver\Redis
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
        return new \DB\Cache\driver\Redis($config);
    }
}