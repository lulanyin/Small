<?php
namespace Small;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Small\Commend\ICommend;
use Small\Http\HttpRouter;

/**
 * APP统一入口
 * Class App
 * @package Small
 */
class App {

    /**
     * 是否是 swoole server 方式运行
     * @var bool
     */
    public static $server = false;

    /**
     * 初始化运行
     * @param bool $server
     * @return IServer
     */
    public static function init(bool $server = false){
        self::before();
        if($server){
            self::$server = true;
            //运行 server 服务，由 Swoole 拓展支持
            echo "+---------------------+\r\n";
            echo "| ♪♪♪♪♪♪ SMALL ♪♪♪♪♪♪ |\r\n";
            echo "|  welcome use server |\r\n";
            echo "|  support by swoole  |\r\n";
            echo "+---------------------+\r\n";
            exit('............ server not support!');
        }else{
            //HTTP，设置前缀、声明使用的路由参数
            $path = $_SERVER['PATH_INFO'] ?? "";
            $path = !empty($path) ? $path : ($_SERVER['REQUEST_URI'] ?? "");
            return new HttpRouter($path);
        }
    }

    /**
     * CMD运行
     */
    public static function cmd(){
        self::before();
        //
        echo "+-----------------+\r\n";
        echo "| ♪♪♪♪ SMALL ♪♪♪♪ |\r\n";
        echo "| welcome use cmd |\r\n";
        echo "+-----------------+\r\n";
        $argv = $_SERVER['argv'] ?? [];
        if(count($argv)>=2){
            $prefix = Config::get("server.commend");
            $commend = $prefix.str_replace(".", "\\", $argv[1]);
            if(class_exists($commend)){
                $params = count($argv)>2 ? array_slice($argv, 2) : [];
                if(!empty($params)){
                    $array = [];
                    foreach($params as $param){
                        $arr = explode("=", $param);
                        $array[$arr[0]] = $arr[1] ?? true;
                        $array[$arr[0]] = $array[$arr[0]]=="true" ? true : ($array[$arr[0]]=="false" ? false : $array[$arr[0]]);
                    }
                    $params = $array;
                }
                $cmd = new $commend($params);
                if($cmd instanceof ICommend){
                    echo date("Y/m/d H:i:s")." running ... ".PHP_EOL;
                    return $cmd;
                }else{
                    exit("not a commend".PHP_EOL);
                }
            }else{
                exit ("not commend found {$commend}".PHP_EOL);
            }
        }else{
            exit ("not commend".PHP_EOL);
        }
    }

    /**
     * 运行前常规检测
     */
    public static function before(){
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