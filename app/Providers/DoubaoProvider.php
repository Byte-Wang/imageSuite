<?php

namespace App\Providers;

use App\Utils\Base64Helper;
use App\Utils\ImageHelper;

class DoubaoProvider implements ProviderInterface
{
    private const ANTI_AI = 'authentic real-world photography, natural skin imperfections, genuine fabric texture, no synthetic look, no CGI quality, no heavy post-processing, no artificial colors, no mannequin appearance, candid natural lighting';

    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string
    {
        $url = $baseUrl ?: 'https://ark.cn-beijing.volces.com/api/v3/images/generations';
        $timeout = $options['request_timeout'] ?? 180;

        $effectivePrompt = rtrim($prompt, '. ') . '. ' . self::ANTI_AI;

        $body = [
            'model' => $model,
            'prompt' => $effectivePrompt,
            'size' => '2048x2048',
            'response_format' => 'url',
            'watermark' => false,
            'n' => 1,
        ];

        if ($referenceImage) {
            $refImages = array_filter(array_map('trim', explode(',', $referenceImage)));
            $body['image'] = array_map(fn($ref) => ImageHelper::toImageInput($ref), $refImages);
        }

        $result = ImageHelper::httpPost($url, [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ], $body, $timeout);

        $imgUrl = $result['data'][0]['url'] ?? '';
        if (!$imgUrl) {
            throw new \RuntimeException('豆包响应中未找到图片URL');
        }

        return ImageHelper::downloadImage($imgUrl, $timeout);
    }
}
