<?php

namespace App\Controllers;

use App\Services\AnalyzeService;

class AnalyzeController
{
    private AnalyzeService $service;

    public function __construct(AnalyzeService $service)
    {
        $this->service = $service;
    }

    public function analyze(array $params): array
    {
        $input = $_POST;

        $images = $input['images'] ?? [];
        if (empty($images)) {
            $uploaded = $this->handleUploads();
            if (!empty($uploaded)) {
                $images = $uploaded;
            }
        }

        if (empty($images)) {
            return ['error' => '请提供至少一张商品图片', 'code' => 400];
        }

        $provider = $input['provider'] ?? '';
        $apiKey = $input['api_key'] ?? '';
        $baseUrl = $input['base_url'] ?? '';
        $model = $input['model'] ?? '';
        $lang = $input['lang'] ?? 'zh';

        try {
            $result = $this->service->analyze($images, $provider, $apiKey, $baseUrl, $model, $lang);
            if (isset($result['error'])) {
                return ['error' => $result['error'], 'code' => 400];
            }
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    private function handleUploads(): array
    {
        $paths = [];
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['images'] ?? [];
        if (empty($files['name'])) return [];

        $count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) continue;

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeName = uniqid('upload_') . '.' . $ext;
            $dest = $uploadDir . '/' . $safeName;

            if (move_uploaded_file($tmpName, $dest)) {
                $paths[] = realpath($dest);
            }
        }

        return $paths;
    }
}
