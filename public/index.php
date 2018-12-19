<?php
/**
 * HTTP 入口文件
 * 需要一定的伪静态支持
 */
require_once dirname(__DIR__)."/bin/bootstrap.php";
App::init()->start();