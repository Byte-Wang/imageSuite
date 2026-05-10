<?php

namespace App\Utils;

class Base64Helper
{
    public static function fixPadding(string $b64): string
    {
        $pad = (4 - strlen($b64) % 4) % 4;
        return $b64 . str_repeat('=', $pad);
    }

    public static function decode(string $b64): string
    {
        if (strpos($b64, 'data:') === 0) {
            $parts = explode(',', $b64, 2);
            $b64 = $parts[1] ?? $b64;
        }
        $decoded = base64_decode(self::fixPadding($b64), true);
        if ($decoded === false) {
            throw new \RuntimeException('Base64 解码失败');
        }
        return $decoded;
    }

    public static function encode(string $data): string
    {
        return base64_encode($data);
    }

    public static function toDataUri(string $pathOrUrl): string
    {
        if (preg_match('#^https?://#', $pathOrUrl) || strpos($pathOrUrl, 'data:') === 0) {
            return $pathOrUrl;
        }

        if (!file_exists($pathOrUrl)) {
            throw new \RuntimeException("参考图文件不存在: {$pathOrUrl}");
        }

        $data = file_get_contents($pathOrUrl);
        $mime = self::detectMime($pathOrUrl);
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    public static function toBase64AndMime(string $pathOrUrl): array
    {
        if (strpos($pathOrUrl, 'data:') === 0) {
            $parts = explode(',', $pathOrUrl, 2);
            $header = $parts[0];
            $b64 = $parts[1] ?? '';
            $mime = explode(';', explode(':', $header, 2)[1])[0];
            return [$b64, $mime];
        }

        if (preg_match('#^https?://#', $pathOrUrl)) {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $data = file_get_contents($pathOrUrl, false, $ctx);
            if ($data === false) {
                throw new \RuntimeException("下载图片失败: {$pathOrUrl}");
            }
            $mime = self::detectMimeFromUrl($pathOrUrl, $http_response_header ?? []);
            return [base64_encode($data), $mime];
        }

        if (!file_exists($pathOrUrl)) {
            throw new \RuntimeException("参考图文件不存在: {$pathOrUrl}");
        }

        $data = file_get_contents($pathOrUrl);
        $mime = self::detectMime($pathOrUrl);
        return [base64_encode($data), $mime];
    }

    public static function detectMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    private static function detectMimeFromUrl(string $url, array $headers): string
    {
        foreach ($headers as $header) {
            if (stripos($header, 'content-type:') === 0) {
                $ct = trim(substr($header, 13));
                return explode(';', $ct)[0];
            }
        }
        return self::detectMime($url);
    }
}
