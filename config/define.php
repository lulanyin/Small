<?php
/**
 * 此文件为最重要的文件，系统环境就靠这文件配置
 */
//时区
date_default_timezone_set("PRC");
//文件夹分隔符
!defined("DS") && define("DS", DIRECTORY_SEPARATOR);
//根目录
define("ROOT",              dirname(__DIR__));
define("BASE_PATH",         ROOT);
//核心方法、类等文件存放的文件夹名
define("RESOURCE",          ROOT."/resource");
//runtime目录
define("RUNTIME",           ROOT."/runtime");
//cache 目录
define("CACHE",             RUNTIME."/cache");
define("CACHE_PATH",        CACHE);
//app根目录
define("APP",               ROOT."/app");

$define = [
    "root"          => ROOT,
    "app"           => "{root}/app",
    "resources"     => "{root}/resources",
    "runtime"       => "{root}/runtime",
    "configs"       => "{root}/config",
    "views"         => "{resources}/views",
    "cache"         => "{runtime}/cache",
    "logs"          => "{runtime}/logs",
    "public"        => "{root}/public",
    "attachment"    => "{public}/attachment"
];
//常量
Config::set("define", \Small\lib\util\Arr::parseValue($define));
//自动加载其它配置
Config::loadConfig();