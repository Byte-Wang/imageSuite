<?php

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';
$providers = require APP_ROOT . '/config/providers.php';

// 合并配置：将 providers 的各个类别（vision/image/video）合并到主配置
$config = array_merge($config, $providers);

date_default_timezone_set($config['app']['timezone']);

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

foreach (['output_dir', 'upload_dir', 'log_dir'] as $dir) {
    $path = $config['storage'][$dir];
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

return $config;
