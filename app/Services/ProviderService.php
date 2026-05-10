<?php

namespace App\Services;

use App\Utils\ConfigHelper;

class ProviderService
{
    private array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function checkProviders(): array
    {
        $result = [];

        $categories = [
            'vision' => '视觉分析',
            'image' => '图像生成',
            'video' => '视频生成',
        ];

        foreach ($categories as $catKey => $catName) {
            $catConfig = $this->providers[$catKey] ?? null;
            if (!$catConfig || !isset($catConfig['providers'])) continue;

            foreach ($catConfig['providers'] as $id => $p) {
                $key = ConfigHelper::getApiKey($p);
                $customUrl = ConfigHelper::getBaseUrl($p);
                $model = ConfigHelper::getModel($p);

                $result[] = [
                    'id' => $p['id'] ?? $id,
                    'category' => $catName,
                    'provider_key' => $id,
                    'name' => $p['name'],
                    'company' => $p['company'] ?? '',
                    'configured' => strlen($key) > 5,
                    'key_preview' => $key ? substr($key, 0, 8) . '...' : '',
                    'custom_url' => strlen($customUrl) > 10 ? $customUrl : null,
                    'model' => $model,
                    'supports_reference' => $p['supports_reference'] ?? false,
                ];
            }
        }

        $configured = array_filter($result, fn($r) => $r['configured']);
        return [
            'all' => $result,
            'configured' => array_values($configured),
            'count' => count($configured),
        ];
    }

    public function getProviderConfig(string $providerId, string $category = 'image'): ?array
    {
        return $this->providers[$category]['providers'][$providerId] ?? null;
    }

    public function resolveApiKey(string $providerId, string $category = 'image'): string
    {
        $p = $this->providers[$category]['providers'][$providerId] ?? null;
        if (!$p) return '';
        return ConfigHelper::getApiKey($p);
    }

    public function resolveModel(string $providerId, string $cliModel = '', string $category = 'image'): string
    {
        $p = $this->providers[$category]['providers'][$providerId] ?? null;
        if (!$p) return '';
        return ConfigHelper::getModel($p, $cliModel);
    }
}
