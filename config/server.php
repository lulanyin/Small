<?php
return [
    //debug
    "debug" => true,
    //网站总开关
    "enable"=> true,
    //常规HTTP路由，如何使用了下方 server,
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
                //文件上传服务
                "files"     => "files",
                //api
                "api"       => "api",
                //admin
                "admin"     => "admin"
            ]
        ]
    ],
    //server配置，若启动ws服务，需要
    "server" => [
        "host"      => "0.0.0.0",
        "port"      => 9600,
        "setting"  => [
            'worker_num'            => 4,
            'max_request'           => 1024,
            'max_connection'        => 256,
            'daemonize'             => 0,
            'dispatch_mode'         => 2,
            'log_file'              => RUNTIME.'/logs/server.log',
            'task_worker_num'       => 4,
            'package_max_length'    => 8092
        ],
        //控制器根目录
        "home"          => "app\server\\",
        //on($event) 默认值如下 {home}\xxxx ...
        "open"          => "websocket\Open",
        "message"       => "websocket\Message",
        "request"       => "http\Request",
        "heartbeat"     => "websocket\Heartbeat",
        "close"         => "websocket\Close",
        "task"          => "Task",
        "finish"        => "Finish",
        //每条进程的连接池数量
        "pool_size"     => 5,
        //HTTP ROUTER
        "http"          => [
            //HTTP命名空间入口
            "home"      => "app\server\http\\",
            //默认 http://域名，对应下方list的一个key
            "default"   => "public",
            //key 为 替换值， value 为访问入口的命名空间，请勿重复
            "list"      => [
                //前台 http://域名/public
                "public"    => "web",
                //后台 http://域名/admin
                "admin"     => "admin",
                //接口 http://域名/api
                "api"       => "api"
            ]
        ],
        //ws
        "websocket"     => [
            "home"      => "app\server\websocket\\"
        ]
    ],
    //命令执行的根目录
    "commend" => "app\cmd\\",
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