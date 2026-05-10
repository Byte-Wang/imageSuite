<?php

namespace App\Providers;

use App\Utils\Base64Helper;
use App\Utils\ImageHelper;

class TongyiProvider implements ProviderInterface
{
    private const DEFAULT_NEGATIVE_PROMPT = 'AI-generated look, artificial, CGI quality, 3D render, digital art, synthetic texture, plastic skin, mannequin-like, uncanny valley, too perfect, robotic pose, oversaturated, HDR, heavy vignette, cinematic color grading, heavy post-processing, dramatic bokeh, excessive lens flare, overprocessed, surreal color, low resolution, blurry, deformed, ugly, bad anatomy, extra limbs, overexposed, underexposed, grainy, noisy, watermark, text, signature, logo, text distortion, bad typography, overlapping text, cheap look, cartoon';

    private function log(string $msg, $data = null): void
    {
        $milli = sprintf('%03d', (microtime(true) - floor(microtime(true))) * 1000);
        $timestamp = date('Y-m-d H:i:s') . '.' . $milli;
        $output = "[{$timestamp}] [TONGYI] {$msg}";
        if ($data !== null) {
            $output .= " | DATA: " . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
        }
        
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/tongyi_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $output . PHP_EOL, FILE_APPEND);
    }

    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string
    {
        $currentModel = $model;
        $attempt = 0;
        $maxRetries = 2;
        $lastError = null;
        $triedModels = [];

        while ($attempt <= $maxRetries) {
            try {
                $triedModels[] = $currentModel;
                return $this->doGenerate($key, $prompt, $baseUrl, $currentModel, $referenceImage, $options, $attempt);
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt <= $maxRetries) {
                    $nextModel = $this->getNextRetryModel($currentModel, $triedModels);
                    if ($nextModel) {
                        $this->log("Generation failed with model {$currentModel}, retrying ({$attempt}/{$maxRetries}) with model {$nextModel}. Error: " . $e->getMessage());
                        $currentModel = $nextModel;
                        continue;
                    }
                }
                break;
            }
        }

        throw $lastError;
    }

    private function getNextRetryModel(string $failedModel, array $triedModels): ?string
     {
         $providersPath = dirname(__DIR__, 2) . '/config/providers.php';
         if (!file_exists($providersPath)) return null;
         
         $config = require $providersPath;
         // 改为从 tongyi 的配置中读取 reference_models
         $refModels = $config['image']['providers']['tongyi']['reference_models'] ?? [];
         
         foreach ($refModels as $m) {
            if (!in_array($m, $triedModels)) {
                return $m;
            }
        }
        
        return null;
    }

    private function doGenerate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = [], int $attempt = 0): string
    {
        $timeout = $options['request_timeout'] ?? 180;
        $pollMaxWait = $options['poll_max_wait'] ?? 600;
        $negativePrompt = $options['negative_prompt'] ?? '';
        $isWan = $this->isWanModel($model);

        // 如果是重试且没有指定 baseUrl，根据模型类型重新选择默认 URL
        $url = $baseUrl;
        if (!$url) {
            $url = $isWan
                ? 'https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/generation'
                : 'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation';
        }

        $content = [];
        if ($referenceImage) {
            $refImages = array_filter(array_map('trim', explode(',', $referenceImage)));
            foreach ($refImages as $refPath) {
                $content[] = ImageHelper::toTongyiImageEntry($refPath);
            }
        }
        $content[] = ['text' => $prompt];

        $headers = [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ];
        if ($isWan) {
            $headers['X-DashScope-Async'] = 'enable';
        }

        $params = [
            'size' => $isWan ? '2048*2048' : '1024*1024',
            'n' => 1,
            'watermark' => false,
        ];
        if (!$isWan) {
            $params['prompt_extend'] = false;
        }
        $neg = $negativePrompt ?: self::DEFAULT_NEGATIVE_PROMPT;
        if ($neg) {
            $params['negative_prompt'] = mb_substr($neg, 0, 500);
        }

        $body = [
            'model' => $model,
            'input' => ['messages' => [['role' => 'user', 'content' => $content]]],
            'parameters' => $params,
        ];

        $this->log("POST REQUEST (Attempt {$attempt}) to {$url}", [
            'model' => $model,
            'prompt' => $prompt,
            'has_ref' => !empty($referenceImage)
        ]);

        $data = ImageHelper::httpPost($url, $headers, $body, $timeout);

        $this->log("POST RESPONSE (Attempt {$attempt}) from {$url}", $data);

        if ($isWan) {
            $taskId = $data['output']['task_id'] ?? '';
            if (!$taskId) {
                throw new \RuntimeException('通义万象未返回 task_id: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            }
            $imgUrl = $this->pollTask($key, $taskId, $pollMaxWait);
            if (strpos($imgUrl, 'data:') === 0 || strlen($imgUrl) > 500) {
                $b64 = strpos($imgUrl, ',') !== false ? explode(',', $imgUrl, 2)[1] : $imgUrl;
                return Base64Helper::decode($b64);
            }
            return ImageHelper::downloadImage($imgUrl, $timeout);
        }

        $imgUrl = $data['output']['choices'][0]['message']['content'][0]['image'] ?? '';
        if (!$imgUrl) {
            throw new \RuntimeException('通义响应中未找到图片URL');
        }
        return ImageHelper::downloadImage($imgUrl, $timeout);
    }

    private function isWanModel(string $model): bool
    {
        return str_starts_with($model, 'wan');
    }

    private function pollTask(string $key, string $taskId, int $maxWait = 300): string
    {
        $url = "https://dashscope.aliyuncs.com/api/v1/tasks/{$taskId}";
        $headers = ['Authorization' => "Bearer {$key}"];
        $elapsed = 0;
        $interval = 3;

        while ($elapsed < $maxWait) {
            sleep($interval);
            $elapsed += $interval;

            $this->log("GET POLL REQUEST to {$url} (elapsed: {$elapsed}s)");
            $result = ImageHelper::httpGet($url, $headers, 30);
            $this->log("GET POLL RESPONSE from {$url}", $result);

            $status = $result['output']['task_status'] ?? '';

            if ($status === 'SUCCEEDED') {
                $choices = $result['output']['choices'] ?? [];
                if (!empty($choices)) {
                    $content = $choices[0]['message']['content'] ?? [];
                    if (!empty($content)) {
                        return $content[0]['image'] ?? '';
                    }
                }
                $results = $result['output']['results'] ?? [];
                if (!empty($results)) {
                    return $results[0]['url'] ?? $results[0]['b64_image'] ?? '';
                }
                throw new \RuntimeException('通义任务成功但无结果');
            }

            if (in_array($status, ['FAILED', 'UNKNOWN'])) {
                throw new \RuntimeException('通义任务失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            $interval = min($interval + 2, 10);
        }

        throw new \RuntimeException("通义异步任务超时 ({$maxWait}s): task_id={$taskId}");
    }
}
