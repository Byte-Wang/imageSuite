<?php

namespace App\Providers;

use App\Utils\Base64Helper;
use App\Utils\ImageHelper;

class OpenAIProvider implements ProviderInterface
{
    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string
    {
        $timeout = $options['request_timeout'] ?? 360;
        $isGptImage = $model && str_starts_with($model, 'gpt-image');

        if ($referenceImage) {
            if (!$isGptImage && str_starts_with($model, 'dall-e')) {
                return $this->textToImage($key, $prompt, $baseUrl, $model, $timeout);
            }
            return $this->imageToImage($key, $prompt, $baseUrl, $model, $referenceImage, $timeout);
        }

        return $this->textToImage($key, $prompt, $baseUrl, $model, $timeout);
    }

    private function textToImage(string $key, string $prompt, string $baseUrl, string $model, int $timeout): string
    {
        $url = $baseUrl
            ? rtrim($baseUrl, '/') . '/v1/images/generations'
            : 'https://api.openai.com/v1/images/generations';

        $body = [
            'model' => $model ?: 'dall-e-3',
            'prompt' => $prompt,
            'size' => '1024x1024',
            'quality' => 'hd',
            'response_format' => 'b64_json',
            'n' => 1,
        ];

        $result = ImageHelper::httpPost($url, [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ], $body, $timeout);

        $b64 = $result['data'][0]['b64_json'] ?? '';
        if (!$b64) {
            throw new \RuntimeException('OpenAI 响应中未找到图片数据');
        }

        return Base64Helper::decode($b64);
    }

    private function imageToImage(string $key, string $prompt, string $baseUrl, string $model, string $referenceImage, int $timeout): string
    {
        $url = $baseUrl
            ? rtrim($baseUrl, '/') . '/images/edits'
            : 'https://api.openai.com/v1/images/edits';

        $refImages = array_filter(array_map('trim', explode(',', $referenceImage)));
        $files = [];
        foreach ($refImages as $i => $refPath) {
            [$b64, $mime] = Base64Helper::toBase64AndMime($refPath);
            $ext = explode('/', $mime)[1] ?? 'jpeg';
            $files["image[]"] = [
                'filename' => "image{$i}.{$ext}",
                'mime' => $mime,
                'content' => Base64Helper::decode($b64),
            ];
        }

        $fields = [
            'model' => $model,
            'prompt' => $prompt,
            'response_format' => 'b64_json',
        ];

        $result = ImageHelper::httpPostMultipart($url, [
            'Authorization' => "Bearer {$key}",
        ], $fields, $files, $timeout);

        $b64Value = $result['data'][0]['b64_json'] ?? '';
        if (!$b64Value) {
            throw new \RuntimeException('OpenAI edits 响应中未找到图片数据');
        }

        if (strpos($b64Value, 'data:') === 0) {
            $b64Value = explode(',', $b64Value, 2)[1];
        }

        return Base64Helper::decode($b64Value);
    }
}
