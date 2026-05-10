<?php

namespace App\Providers;

use App\Utils\Base64Helper;
use App\Utils\ImageHelper;

class GeminiProvider implements ProviderInterface
{
    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string
    {
        $timeout = $options['request_timeout'] ?? 600;
        $isOpenaiFormat = $baseUrl && strpos($baseUrl, '/v1/') !== false;

        if ($isOpenaiFormat) {
            return $this->openaiFormat($key, $prompt, $baseUrl, $model, $referenceImage, $timeout);
        }

        return $this->nativeFormat($key, $prompt, $baseUrl, $model, $referenceImage, $timeout);
    }

    private function openaiFormat(string $key, string $prompt, string $baseUrl, string $model, string $referenceImage, int $timeout): string
    {
        if ($referenceImage) {
            $url = $this->resolveEditsUrl($baseUrl);
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

            $result = ImageHelper::httpPostMultipart($url, [
                'Authorization' => "Bearer {$key}",
            ], ['model' => $model ?: 'nano-banana', 'prompt' => $prompt, 'n' => 1], $files, $timeout);

            return $this->extractOpenaiImage($result);
        }

        $url = rtrim($baseUrl, '/');
        if (!str_ends_with($url, '/images/generations')) {
            $url .= '/images/generations';
        }

        $result = ImageHelper::httpPost($url, [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ], [
            'model' => $model ?: 'gemini-3.1-flash-image-preview',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1:1',
            'quality' => 'low',
        ], $timeout);

        return $this->extractOpenaiImage($result);
    }

    private function resolveEditsUrl(string $baseUrl): string
    {
        $clean = rtrim($baseUrl, '/');
        if (str_ends_with($clean, '/images/edits') || str_ends_with($clean, '/v1/images/edits')) {
            return $clean;
        }
        if (strpos($clean, '/images/generations') !== false) {
            return str_replace('/images/generations', '/images/edits', $clean);
        }
        if (str_ends_with($clean, '/v1')) {
            return $clean . '/images/edits';
        }
        if (strpos($clean, '/v1/') !== false) {
            $parts = explode('/v1/', $clean);
            return $parts[0] . '/v1/images/edits';
        }
        return $clean . '/images/edits';
    }

    private function extractOpenaiImage(array $data): string
    {
        if (isset($data['data'][0]['b64_json'])) {
            return Base64Helper::decode($data['data'][0]['b64_json']);
        }
        if (isset($data['data'][0]['url'])) {
            return ImageHelper::downloadImage($data['data'][0]['url']);
        }
        throw new \RuntimeException('Gemini OpenAI 格式响应中未找到图片数据');
    }

    private function nativeFormat(string $key, string $prompt, string $baseUrl, string $model, string $referenceImage, int $timeout): string
    {
        $isOfficial = !$baseUrl;
        $url = $baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta/models/' . ($model ?: 'gemini-3.1-flash-image-preview') . ':generateContent';

        if ($isOfficial) {
            $reqUrl = (strpos($url, '?') !== false ? $url : $url . "?key={$key}");
            $headers = ['x-goog-api-key' => $key, 'Content-Type' => 'application/json'];
        } else {
            $reqUrl = $url;
            $headers = ['Authorization' => "Bearer {$key}", 'Content-Type' => 'application/json'];
        }

        $parts = [];
        if ($referenceImage) {
            $refImages = array_filter(array_map('trim', explode(',', $referenceImage)));
            foreach ($refImages as $refPath) {
                [$b64, $mime] = Base64Helper::toBase64AndMime($refPath);
                $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $b64]];
            }
        }
        $parts[] = ['text' => $prompt];

        $result = ImageHelper::httpPost($reqUrl, $headers, [
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'responseModalities' => ['IMAGE'],
                'imageConfig' => ['aspectRatio' => '1:1', 'imageSize' => '2K'],
            ],
        ], $timeout);

        foreach ($result['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (isset($part['inlineData']['data'])) {
                return Base64Helper::decode($part['inlineData']['data']);
            }
        }

        throw new \RuntimeException('Gemini 响应中未找到图片数据');
    }
}
