<?php

// 处理静态文件（CSS/JS/图片等）
$uri = $_SERVER['REQUEST_URI'];
$path = urldecode(parse_url($uri, PHP_URL_PATH));
// 统一处理路径，去除可能的重复斜杠并确保以 / 开头
$normalizedPath = '/' . ltrim($path, '/');

if (strpos($normalizedPath, '/public/') === 0 || strpos($normalizedPath, '/storage/') === 0) {
    $filePath = __DIR__ . $normalizedPath;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'html' => 'text/html',
        ];
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        header("Content-Type: {$mimeType}");
        readfile($filePath);
        exit;
    }
}

$config = require __DIR__ . '/app/bootstrap.php';

use App\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use App\Controllers\AnalyzeController;
use App\Controllers\GenerateController;
use App\Controllers\VideoController;
use App\Controllers\ProviderController;
use App\Controllers\ConfigController;
use App\Services\AnalyzeService;
use App\Services\GenerateService;
use App\Services\VideoService;
use App\Services\ProviderService;

$router = new Router();
$router->addMiddleware(new CorsMiddleware());
$router->addMiddleware(new JsonMiddleware());

$analyzeService = new AnalyzeService($config);
$generateService = new GenerateService($config);
$videoService = new VideoService($config);
$providerService = new ProviderService($config);

$analyzeController = new AnalyzeController($analyzeService);
$generateController = new GenerateController($generateService);
$videoController = new VideoController($videoService);
$providerController = new ProviderController($providerService);
$configController = new ConfigController($config);

$router->post('/api/analyze', [$analyzeController, 'analyze']);
$router->post('/api/generate', [$generateController, 'generate']);
$router->get('/api/generate/{task_id}', [$generateController, 'status']);
$router->post('/api/video', [$videoController, 'generate']);
$router->get('/api/providers', [$providerController, 'index']);
$router->get('/api/config', [$configController, 'index']);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri);
