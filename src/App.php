<?php
namespace Small;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Small\commend\ICommend;
use Small\http\Router;
use Small\server\Server;

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
     * @return Router|Server
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
            return new Server();
        }else{
            //HTTP，设置前缀、声明使用的路由参数
            $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "";
            $path = empty($path) ? ($_SERVER['REQUEST_URI'] ?? "") : $path;
            return new Router($path);
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