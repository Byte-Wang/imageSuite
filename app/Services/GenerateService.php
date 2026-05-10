<?php

namespace App\Services;

use App\Providers\OpenAIProvider;
use App\Providers\GeminiProvider;
use App\Providers\StabilityProvider;
use App\Providers\TongyiProvider;
use App\Providers\DoubaoProvider;
use App\Utils\ErrorLogger;
use App\Utils\ImageHelper;
use App\Utils\ConfigHelper;

class GenerateService
{
    private array $config;
    private PromptBuilder $promptBuilder;
    private ErrorLogger $errorLogger;

    private const PROVIDER_MAP = [
        'openai' => OpenAIProvider::class,
        'gemini' => GeminiProvider::class,
        'stability' => StabilityProvider::class,
        'tongyi' => TongyiProvider::class,
        'doubao' => DoubaoProvider::class,
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->promptBuilder = new PromptBuilder();
        $this->errorLogger = new ErrorLogger($config['storage']['log_dir']);
    }

    public function generate(array $product, string $provider, string $apiKey = '', string $baseUrl = '', string $model = '', array $options = []): array
    {
        $providerConfig = $this->config['image']['providers'][$provider] ?? null;
        if (!$providerConfig) {
            throw new \RuntimeException("未知供应商：{$provider}");
        }

        $key = $apiKey ?: ConfigHelper::getApiKey($providerConfig);
        if (!$key) {
            throw new \RuntimeException("未找到 {$provider} 的 API Key，请在 providers.php 的 'api_key' 字段填写。");
        }

        $resolvedBaseUrl = $baseUrl ?: ConfigHelper::getBaseUrl($providerConfig);
        $resolvedModel = $model ?: ConfigHelper::getModel($providerConfig);

        $this->normalizeProduct($product);

        $desc = $this->resolveDescription($product);
        $sellingPoints = $product['selling_points'] ?? [];
        $garmentPosition = $product['garment_position'] ?? 'non-apparel';
        $targetScenes = $product['target_scenes'] ?? [];
        $productStyle = $product['product_style'] ?? '';
        $printDesignLock = $product['print_design_lock'] ?? '';
        $targetAudience = $product['target_audience'] ?? '';
        $targetSceneEnvs = $product['target_scene_envs'] ?? [];
        $productType = $product['product_type'] ?? '';
        $modelEthnicity = $product['model_ethnicity'] ?? 'asian';
        $inputImageType = $product['input_image_type'] ?? 'flat_lay';

        $types = $options['types'] ?? explode(',', $this->config['defaults']['image_types']);
        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }

        $lang = $options['lang'] ?? $this->config['defaults']['lang'];
        $templateSet = $options['template_set'] ?? $this->config['defaults']['template_set'];
        $modelStyle = $options['model_style'] ?? $this->config['defaults']['model_style'];
        $keyFeaturesStyle = $options['key_features_style'] ?? '';
        $negativePrompt = $options['negative_prompt'] ?? '';
        $modelImage = $options['model_image'] ?? '';
        $productImages = $options['product_images'] ?? [];
        $perTypeTemplates = $options['per_type_templates'] ?? [];

        $outputDir = empty($options['output_dir']) ? $this->config['storage']['output_dir'] : $options['output_dir'];
        $taskId = $options['task_id'] ?? uniqid('task_');
        $taskOutputDir = rtrim($outputDir, '/') . '/' . $taskId;
        if (!is_dir($taskOutputDir)) {
            mkdir($taskOutputDir, 0755, true);
        }
        $taskOutputDir = realpath($taskOutputDir); // 确保是规范化的绝对路径

        if (count($productImages) === 1 && !in_array('three_angle_view', $types)) {
            $idx = array_search('multi_scene', $types);
            if ($idx !== false) {
                array_splice($types, $idx, 0, ['three_angle_view']);
            } else {
                $types[] = 'three_angle_view';
            }
        }

        $providerInstance = new (self::PROVIDER_MAP[$provider])();
        $results = [];
        $generatedModelImg = '';
        $isFirst = true;

        foreach ($types as $typeId) {
            if (!$isFirst) {
                // 间隔 500ms 逐个调用，避免一口气调用大量接口
                usleep(1500000);
            }
            $isFirst = false;

            try {
                $slot = $this->promptBuilder->getImageSlot($typeId);
                $productRef = $productImages[$slot] ?? ($productImages[0] ?? '');
                $hasProductRef = !empty($productRef);

                if (in_array($typeId, ['model', 'lifestyle', 'multi_scene'])) {
                    if ($modelImage) {
                        $refImg = $productRef ? "{$modelImage},{$productRef}" : $modelImage;
                        $hasModelRef = true;
                    } elseif ($typeId !== 'model' && $generatedModelImg) {
                        $refImg = $productRef ? "{$generatedModelImg},{$productRef}" : $generatedModelImg;
                        $hasModelRef = true;
                    } elseif ($typeId !== 'model' && file_exists($taskOutputDir . '/模特展示图.jpg')) {
                        $existingModel = realpath($taskOutputDir . '/模特展示图.jpg');
                        $refImg = $productRef ? "{$existingModel},{$productRef}" : $existingModel;
                        $hasModelRef = true;
                    } else {
                        $refImg = $productRef;
                        $hasModelRef = false;
                    }
                } else {
                    $refImg = $productRef;
                    $hasModelRef = false;
                }

                $tpl = $perTypeTemplates[$typeId] ?? $templateSet;

                $prompt = $this->promptBuilder->buildPrompt($typeId, $desc, $sellingPoints, [
                    'model_style' => $modelStyle,
                    'has_model_ref' => $hasModelRef,
                    'lang' => $lang,
                    'garment_position' => $garmentPosition,
                    'print_design_lock' => $printDesignLock,
                    'has_product_ref' => $hasProductRef,
                    'input_image_type' => $inputImageType,
                    'template_set' => $tpl,
                    'key_features_style' => $keyFeaturesStyle,
                    'per_type_templates' => $perTypeTemplates,
                    'target_scenes' => $targetScenes,
                    'product_style' => $productStyle,
                    'target_audience' => $targetAudience,
                    'target_scene_envs' => $targetSceneEnvs,
                    'product_type' => $productType,
                    'model_ethnicity' => $modelEthnicity,
                ]);

                $imgBytes = $providerInstance->generate($key, $prompt, $resolvedBaseUrl, $resolvedModel, $refImg, [
                    'negative_prompt' => $negativePrompt,
                    'request_timeout' => $options['request_timeout'] ?? $this->config['defaults']['request_timeout'],
                    'poll_max_wait' => $options['poll_max_wait'] ?? $this->config['defaults']['poll_max_wait'],
                ]);

                $zhName = PromptBuilder::TYPE_NAMES_ZH[$typeId] ?? $typeId;
                $outPath = $taskOutputDir . "/{$zhName}.jpg";
                ImageHelper::saveImage($imgBytes, $outPath);

                // 将绝对路径转换为相对 URL 路径（以 /storage/ 开头）供前端使用
                $rootDir = realpath(dirname(__DIR__, 2));
                $relativePath = str_replace($rootDir, '', $outPath);
                
                $results[$typeId] = ['status' => 'ok', 'path' => $relativePath, 'name' => $zhName];

                if ($typeId === 'model') {
                    // 这里保持绝对路径，以便后续类型作为参考图时能正确找到文件
                    $generatedModelImg = $outPath;
                }
            } catch (\Throwable $e) {
                $logPath = $this->errorLogger->writeErrorLog($provider, $typeId, $prompt ?? '', $e);
                $results[$typeId] = ['status' => 'error', 'error' => $e->getMessage(), 'log' => realpath($logPath)];
            }
        }

        $summaryPath = $taskOutputDir . '/generate_result.json';
        file_put_contents($summaryPath, json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 将 output_dir 也转换为相对 URL 路径
        $rootDir = realpath(dirname(__DIR__, 2));
        $relativeOutputDir = str_replace($rootDir, '', $taskOutputDir);

        return [
            'task_id' => $taskId,
            'output_dir' => $relativeOutputDir,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'success' => count(array_filter($results, fn($r) => $r['status'] === 'ok')),
                'failed' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
            ],
        ];
    }

    private function normalizeProduct(array &$product): void
    {
        if (isset($product['usage_scenes']) && !isset($product['target_scenes'])) {
            $product['target_scenes'] = $product['usage_scenes'];
        }
        if (empty($product['product_style'])) {
            $product['product_style'] = $product['product_subtype'] ?? $product['product_category'] ?? '';
        }
    }

    private function resolveDescription(array $product): string
    {
        $desc = $product['product_description_for_prompt'] ?? '';
        if ($desc) return $desc;

        $productName = $product['product_name'] ?? 'product';
        $vf = $product['visual_features'] ?? [];

        if (is_array($vf) && !empty($vf)) {
            if (isset($vf['main_color'])) {
                $parts = [$productName];
                foreach (['main_color', 'pattern', 'neckline', 'silhouette', 'hemline', 'fabric_texture'] as $key) {
                    if (!empty($vf[$key])) $parts[] = $vf[$key];
                }
                return implode(' ', $parts);
            }
            return trim("{$productName} " . implode(' ', array_slice($vf, 0, 4)));
        }

        return $productName;
    }
}
