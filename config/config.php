<?php

return [
    'app' => [
        'name' => 'E-Commerce Image Suite',
        'debug' => false,
        'timezone' => 'Asia/Shanghai',
        'upload_max_size' => 10 * 1024 * 1024,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'storage' => [
        'output_dir' => __DIR__ . '/../storage/output',
        'upload_dir' => __DIR__ . '/../storage/uploads',
        'log_dir' => __DIR__ . '/../storage/logs',
    ],

    'defaults' => [
        'lang' => 'zh',
        'template_set' => 1,
        'model_style' => 'standard',
        'request_timeout' => 360,
        'poll_max_wait' => 600,
        'image_types' => 'white_bg,key_features,selling_pt,material,lifestyle,model,multi_scene,ecommerce_detail',
    ],
];
