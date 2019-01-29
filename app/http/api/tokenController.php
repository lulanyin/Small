<?php
namespace app\http\api;

use Small\http\HttpController;
use Small\lib\cache\Cache;

/**
 * Class tokenController
 * @package app\http\api
 */
class tokenController extends HttpController{

    /**
     * 验证Token有效性
     * @param mixed ...$args
     * @return mixed|void
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        $token = $this->getPostData("trim:token", null, '缺少TOKEN参数');
        if(strlen($token)==32){
            if($info = Cache::get($token)) {
                //判断过期时间
                $expTime = $info['exp_time'] ?? 0;
                if ($expTime > time()) {
                    $this->response(0, null, [
                        "token"     => $token
                    ]);
                }
            }
            $this->response("TOKEN无效");
        }else{
            $this->response("TOKEN参数错误");
        }
    }

}