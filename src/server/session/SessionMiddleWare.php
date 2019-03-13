<?php
/**
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2019-03-06
 * Time: 19:02
 */
namespace Small\server\session;

use Small\lib\util\Str;
use Small\middleware\IMiddleWare;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * 实现Session的中间键
 * Class SessionMiddleWare
 * @package Small\server\session
 */
class SessionMiddleWare implements IMiddleWare{

    /**
     * @param $controller
     * @param mixed ...$args
     * @return array
     */
    public function process($controller, ...$args)
    {
        $request = $args[0] ?? null;
        $response = $args[1] ?? null;
        if($request instanceof Request && $response instanceof Response){
            if(empty($request->cookie) || !isset($request->cookie['PHPSESSION_ID'])) {
                $response->cookie('PHPSESSION_ID', sha1(microtime(true) . Str::randomNumber(12)), time() + 3600);
            }
            if(is_array($request->cookie) && isset($request->cookie['PHPSESSION_ID'])){
                $response->cookie('PHPSESSION_ID', $request->cookie['PHPSESSION_ID'], time() + 3600);
            }
        }
        return [$controller, $request, $response];
    }
}