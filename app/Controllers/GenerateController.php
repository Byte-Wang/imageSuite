<?php

namespace App\Controllers;

use App\Services\GenerateService;

class GenerateController
{
    private GenerateService $service;

    public function __construct(GenerateService $service)
    {
        $this->service = $service;
    }

    public function generate(array $params): array
    {
        $input = $_POST;
        $productJson = $input['product'] ?? '';

        if (!$productJson) {
            return ['error' => '请提供商品信息 (product)', 'code' => 400];
        }

        $product = json_decode($productJson, true);
        if ($product === null) {
            return ['error' => '商品信息 JSON 解析失败', 'code' => 400];
        }

        $provider = $input['provider'] ?? '';
        if (!$provider) {
            return ['error' => '请指定供应商 (provider)', 'code' => 400];
        }

        $apiKey = $input['api_key'] ?? '';
        $baseUrl = $input['base_url'] ?? '';
        $model = $input['model'] ?? '';

        $productImages = $this->handleProductImages();
        $modelImage = $input['model_image'] ?? '';

        $options = [
            'types' => $input['types'] ?? '',
            'lang' => $input['lang'] ?? 'zh',
            'template_set' => (int)($input['template_set'] ?? 1),
            'model_style' => $input['model_style'] ?? 'standard',
            'key_features_style' => $input['key_features_style'] ?? '',
            'negative_prompt' => $input['negative_prompt'] ?? '',
            'model_image' => $modelImage,
            'product_images' => $productImages,
            'per_type_templates' => $this->parsePerTypeTemplates($input['per_type_templates'] ?? ''),
            'request_timeout' => (int)($input['request_timeout'] ?? 360),
            'poll_max_wait' => (int)($input['poll_max_wait'] ?? 600),
            'output_dir' => $input['output_dir'] ?? null,
        ];

        try {
            $result = $this->service->generate($product, $provider, $apiKey, $baseUrl, $model, $options);
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    public function status(array $params): array
    {
        $taskId = $params['task_id'] ?? '';
        if (!$taskId) {
            return ['error' => '请提供 task_id', 'code' => 400];
        }

        $outputDir = dirname(__DIR__, 2) . '/storage/output/' . $taskId;
        $summaryPath = $outputDir . '/generate_result.json';

        if (!file_exists($summaryPath)) {
            return ['error' => '任务不存在', 'code' => 404];
        }

        $summary = json_decode(file_get_contents($summaryPath), true);
        return ['success' => true, 'data' => [
            'task_id' => $taskId,
            'results' => $summary,
        ]];
    }

    private function handleProductImages(): array
    {
        $paths = [];
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['product_images'] ?? [];
        if (empty($files['name'])) return [];

        $count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) continue;

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeName = uniqid('product_') . '.' . $ext;
            $dest = $uploadDir . '/' . $safeName;

            if (move_uploaded_file($tmpName, $dest)) {
                $paths[] = realpath($dest);
            }
        }

        return $paths;
    }

    private function parsePerTypeTemplates(string $input): array
    {
        $result = [];
        if (!$input) return $result;

        foreach (explode(',', $input) as $pair) {
            $pair = trim($pair);
            if (strpos($pair, ':') !== false) {
                [$k, $v] = explode(':', $pair, 2);
                $result[trim($k)] = (int)trim($v);
            }
        }

        return $result;
    }
}
