<?php

namespace App\Providers;

use App\Utils\Base64Helper;
use App\Utils\ImageHelper;

class StabilityProvider implements ProviderInterface
{
    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string
    {
        $url = $baseUrl ?: 'https://api.stability.ai/v2beta/stable-image/generate/core';
        $timeout = $options['request_timeout'] ?? 180;

        $fields = [
            'prompt' => $prompt,
            'output_format' => 'jpeg',
            'aspect_ratio' => '1:1',
        ];

        $result = ImageHelper::httpPostMultipart($url, [
            'Authorization' => "Bearer {$key}",
            'Accept' => 'application/json',
        ], $fields, ['none' => ['filename' => 'none', 'mime' => 'text/plain', 'content' => '']], $timeout);

        $b64 = $result['image'] ?? '';
        if (!$b64) {
            throw new \RuntimeException('Stability 响应中未找到图片数据');
        }

        return Base64Helper::decode($b64);
    }
}
