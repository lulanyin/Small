<?php
return [
    'icon' => 'shop cart',
    'name' => '商城',
    'weight'=> 3,
    'list' => [
        ['icon'=>'content', 'name'=>'商品列表', 'url'=>'shop/pro'],
        ['icon'=>'yen', 'name'=>'库存/价格', 'url'=>'shop/sku'],
        ['icon'=>'content', 'name'=>'订单管理', 'url'=>'shop/order'],
        ['icon'=>'ship', 'name'=>'快递列表', 'url'=>'shop/ship'],
        ['icon'=>'undo', 'name'=>'退货管理', 'url'=>'shop/return'],
    ]
];