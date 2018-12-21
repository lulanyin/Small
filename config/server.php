<?php
return [
    //debug
    "debug" => true,
    //网站总开关
    "enable"=> true,
    //路由
    "route" => [
        // app\http
        "http"  => [
            //HTTP命名空间入口
            "home"      => "app\http\\",
            //默认 http://域名，对应下方list的一个key
            "default"   => "public",
            //key 为 替换值， value 为访问入口的命名空间，请勿重复
            "list"      => [
                //前台 http://域名/public
                "public"    => "web",
                //后台 http://域名/admin
                "admin"     => "admin",
                //商户后台
                "shop"      => "shop",
                //接口 http://域名/api
                "api"       => "api"
            ]
        ]
    ],
    //WebSocket配置，若启动ws服务，需要
    "websocket" => [
        "host"      => "0.0.0.0",
        "port"      => 9600,
        "setting"  => [
            'worker_num'            => 2,
            'max_request'           => 1024,
            'max_connection'        => 256,
            'daemonize'             => 0,
            'dispatch_mode'         => 2,
            'log_file'              => RUNTIME.'/logs/ws.log',
            'task_worker_num'       => 4,
            'package_max_length'    => 8092
        ],
        //控制器根目录
        "home"      => "app\websocket\\",
        //on($event) 默认值如下 {home}/open ...
        "open"          => "open",
        "message"       => "message",
        "heartbeat"     => "heartbeat",
        "close"         => "close",
        "task"          => "task",
        "finish"        => "finish",
    ],
    //命令执行的根目录
    "commend" => "app\cmd\\",
    //数据库配置
    "mysql" => [
        "default"   => [
            'host'      => '127.0.0.1',
            'port'      => 3306,
            'user'      => 'root',
            'password'  => '123456',
            'pass'      => '123456',//兼容DB类
            'database'  => 'small',
            "charset"   => "utf8",
            "prefix"    => 'pre_',
            'timeout'   => 5
        ],
        /*
        "read" => [ ... ],
        "write"=> [ ... ]
        */
    ],
    //redis
    "redis" => [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => 'redis_small_',
    ],
    //其它设置
    "setting"   => [
        //cookie加密字符
        "cookies_hash_key"  => "1v2b3n4t5y6u",
        "cookies_path"      => "/"
    ],
    //语言
    "language"  => [
        //默认
        "default"   => "zh",
        //有多少种语言文件
        "list"  => [
            //中文
            "zh",
            //英文
            "en"
        ]
    ]
];