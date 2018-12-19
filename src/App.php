<?php
namespace Small;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Small\http\Router;
use Small\websocket\Server;

/**
 * APP统一入口
 * Class App
 * @package Small
 */
class App {

    /**
     * 初始化运行
     * @param bool $ws 是不是运行websocket服务
     * @return IServer
     */
    public static function init(bool $ws = false){
        //检测常量
        self::checkConst([
            "ROOT", "DS", "RESOURCE", "RUNTIME", "CACHE", "APP"
        ]);
        //加载配置
        $define = Config::get("define");
        if(!$define){
            exit("未检测到站点常量配置".PHP_EOL);
        }
        $server = Config::get("server");
        if(!$server){
            exit("未检测到站点服务配置".PHP_EOL);
        }
        $domain = Config::get("domain");
        if(!$domain){
            exit("未检测到站点域名配置".PHP_EOL);
        }
        //注解实现
        AnnotationRegistry::registerLoader(function ($class){
            return class_exists($class) || interface_exists($class);
        });
        if($ws){
            //运行 websocket 服务，由 Swoole 拓展支持
            return new Server();
        }else{
            //HTTP，设置前缀
            $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "";
            $path = empty($path) ? ($_SERVER['REQUEST_URI'] ?? "") : $path;
            return new Router($path);
        }
    }

    /**
     * CMD运行
     */
    public static function cmd(){

    }

    /**
     * 检测常量
     * @param $nameList
     */
    public static function checkConst($nameList){
        $nameList = is_array($nameList) ? $nameList : [$nameList];
        foreach ($nameList as $item){
            if(!defined($item)){
                exit("未检测到常量设置：{$item}".PHP_EOL);
            }
        }
    }
}