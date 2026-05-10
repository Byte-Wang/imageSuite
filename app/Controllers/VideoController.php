<?php

namespace App\Controllers;

use App\Services\VideoService;

class VideoController
{
    private VideoService $service;

    public function __construct(VideoService $service)
    {
        $this->service = $service;
    }

    public function generate(array $params): array
    {
        $input = $_POST;
        $apiKey = $input['api_key'] ?? '';
        $model = $input['model'] ?? '';

        $images = $this->handleUploads();
        if (empty($images)) {
            $imageUrls = $input['images'] ?? [];
            if (!empty($imageUrls) && is_array($imageUrls)) {
                $images = $imageUrls;
            }
        }

        if (empty($images)) {
            $outputDir = dirname(__DIR__, 2) . '/storage/output';
            $candidates = ['白底主图.jpg', '模特展示图.jpg', '场景展示图.jpg', '多场景拼图.jpg', '材质图.jpg'];
            foreach ($candidates as $name) {
                if (file_exists($outputDir . '/' . $name)) {
                    $images[] = realpath($outputDir . '/' . $name);
                }
            }
        }

        $productJson = null;
        if (!empty($input['product_json'])) {
            $productJson = json_decode($input['product_json'], true);
        }

        $options = [
            'prompt' => $input['prompt'] ?? '',
            'audio' => ($input['audio'] ?? '') === 'true',
            'ratio' => $input['ratio'] ?? '16:9',
            'duration' => (int)($input['duration'] ?? 6),
            'max_wait' => (int)($input['max_wait'] ?? 600),
            'product_json' => $productJson,
        ];

        try {
            $result = $this->service->generate($images, $apiKey, $model, $options);
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
            $safeName = uniqid('video_img_') . '.' . $ext;
            $dest = $uploadDir . '/' . $safeName;

            if (move_uploaded_file($tmpName, $dest)) {
                $paths[] = realpath($dest);
            }
        }

        return $paths;
    }
}
