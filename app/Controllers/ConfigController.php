<?php

namespace App\Controllers;

class ConfigController
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function index(array $params): array
    {
        $imageProviders = $this->config['providers']['image']['providers'] ?? [];

        $safeConfig = [
            'app' => [
                'name' => $this->config['app']['name'],
            ],
            'defaults' => $this->config['defaults'],
            'image_types' => \App\Services\PromptBuilder::TYPE_NAMES_ZH,
            'providers' => [],
        ];

        foreach ($imageProviders as $id => $p) {
            $safeConfig['providers'][] = [
                'id' => $p['id'] ?? $id,
                'name' => $p['name'],
                'company' => $p['company'],
                'default_model' => $p['default_model'],
                'supports_reference' => $p['supports_reference'] ?? false,
            ];
        }

        return ['success' => true, 'data' => $safeConfig];
    }
}
