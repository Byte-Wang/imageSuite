<?php

namespace App\Utils;

class ImageHelper
{
    public static function toImageInput(string $pathOrUrl): string
    {
        if (preg_match('#^https?://#', $pathOrUrl) || strpos($pathOrUrl, 'data:') === 0) {
            return $pathOrUrl;
        }

        if (!file_exists($pathOrUrl)) {
            throw new \RuntimeException("参考图文件不存在: {$pathOrUrl}");
        }

        return Base64Helper::toDataUri($pathOrUrl);
    }

    public static function toTongyiImageEntry(string $pathOrUrl): array
    {
        if (preg_match('#^https?://#', $pathOrUrl) || strpos($pathOrUrl, 'data:') === 0) {
            return ['image' => $pathOrUrl];
        }

        if (!file_exists($pathOrUrl)) {
            throw new \RuntimeException("参考图文件不存在: {$pathOrUrl}");
        }

        $dataUri = Base64Helper::toDataUri($pathOrUrl);
        return ['image' => $dataUri];
    }

    public static function saveImage(string $imageData, string $outputPath): bool
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($outputPath, $imageData) !== false;
    }

    public static function downloadImage(string $url, int $timeout = 120): string
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => $timeout, 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $data = file_get_contents($url, false, $ctx);
        if ($data === false) {
            throw new \RuntimeException("下载图片失败: {$url}");
        }
        return $data;
    }

    public static function httpPost(string $url, array $headers, $body, int $timeout = 180): array
    {
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $ctx = stream_context_create($opts);
        $response = file_get_contents($url, false, $ctx);

        if ($response === false) {
            throw new \RuntimeException("HTTP POST 请求失败: {$url}");
        }

        $statusCode = 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $statusCode = (int) $m[1];
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException("HTTP 错误 {$statusCode}: " . mb_substr($response, 0, 500));
        }

        $json = json_decode($response, true);
        if ($json === null) {
            throw new \RuntimeException("JSON 解析失败: " . mb_substr($response, 0, 200));
        }

        return $json;
    }

    public static function httpPostMultipart(string $url, array $headers, array $fields, array $files, int $timeout = 360): array
    {
        $boundary = uniqid('----WebKitFormBoundary');
        $body = '';

        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= "{$value}\r\n";
        }

        foreach ($files as $name => $fileInfo) {
            $filename = $fileInfo['filename'] ?? 'image.jpg';
            $mime = $fileInfo['mime'] ?? 'image/jpeg';
            $content = $fileInfo['content'];
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mime}\r\n\r\n";
            $body .= $content . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }
        $headerLines[] = "Content-Type: multipart/form-data; boundary={$boundary}";
        $headerLines[] = "Content-Length: " . strlen($body);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $ctx = stream_context_create($opts);
        $response = file_get_contents($url, false, $ctx);

        if ($response === false) {
            throw new \RuntimeException("Multipart POST 请求失败: {$url}");
        }

        $statusCode = 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $statusCode = (int) $m[1];
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException("HTTP 错误 {$statusCode}: " . mb_substr($response, 0, 500));
        }

        $json = json_decode($response, true);
        if ($json === null) {
            throw new \RuntimeException("JSON 解析失败: " . mb_substr($response, 0, 200));
        }

        return $json;
    }

    public static function httpGet(string $url, array $headers = [], int $timeout = 30): array
    {
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headerLines),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $ctx = stream_context_create($opts);
        $response = file_get_contents($url, false, $ctx);

        if ($response === false) {
            throw new \RuntimeException("HTTP GET 请求失败: {$url}");
        }

        $statusCode = 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $statusCode = (int) $m[1];
        }

        $json = json_decode($response, true);
        return $json ?: ['raw' => $response, 'status' => $statusCode];
    }
}
