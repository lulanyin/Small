<?php
namespace app\server\http\web;

use app\server\http\RequestController;
use Small\model\models\UserModel;

class indexController extends RequestController{

    public function index(...$args)
    {
        $m = new UserModel();
        $db = $m->where("u.uid", 1);
        //print_r($db->compileToQueryString());
        return $db->first();
    }
}