<?php
return [
    //规定是http还是https，尽量使用 https
    "scheme"            => "http",
    //域名
    "host"              => "small.in",
    //站点完整地址
    "web_url"           => "{scheme}://www.{host}",
    //静态文件地址
    "assets_url"        => "{scheme}://assets.{host}",
    //附件地址
    "attachment_url"    => "{scheme}://attachment.{host}",
    //上传文件地址（上传文件，使用传统的PHP-FPM处理，请绑定域名到/public目录）
    "upload_api"        => "{scheme}://files.{host}",
    //域名路由
    "route" => [
        //路由请勿添加 http:// 或 https://，仅需要域名
        "api" => "api.{@host}",
        "files" => "files.{@host}"
    ],
    //可访问的来访域名，获取当前访问者的域名，如果域名不正确，直接返回404，每个入口都可以设定不同的域名
    "access_origin"     => [
        //API 所有来源都允许
        "api"   => "*",
        //文件上传管理，仅允许特定的域名，要写全，包含http://或https://
        "files" => [
            "{@scheme}://shop.{@host}",
            "{@scheme}://{@host}",
            "{@scheme}://www.{@host}"
        ]
    ],
    //跨域白名单
    "whitelist"         => [

    ],
    //跨域黑名单
    "blacklist"         => [

    ]
];
