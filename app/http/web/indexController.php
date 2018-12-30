<?php
namespace app\http\web;

use Small\http\HttpController;
use Small\model\models\UserModel;

/**
 * 示例，首页
 * Class indexController
 * @package app\http\web
 */
class indexController extends HttpController{

    /**
     *
     * @param mixed ...$args
     * @return mixed|string
     */
    public function index(...$args)
    {
        // TODO: Implement index() method.
        //连接数据库
        $m = new UserModel(1);
        $row = $m->mainQuery()
            ->where('uid', 1)
            ->first();
        echo 'here update';
        return $row;
    }
}