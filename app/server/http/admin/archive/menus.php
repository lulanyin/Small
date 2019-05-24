<?php
return [
    'icon' => 'archive',
    'name' => '文档管理',
    'weight'=> 3,
    'list' => [
        ['icon'=>'folder outline', 'name'=>'档案分类', 'url'=>'archive/categories'],
        ['icon'=>'file word outline', 'name'=>'新闻档案', 'url'=>'archive'],
        ['icon'=>'expand', 'name'=>'焦点图', 'url'=>'archive/focus'],
    ]
];