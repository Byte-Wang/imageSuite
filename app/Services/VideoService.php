<?php

namespace App\Services;

use App\Utils\ImageHelper;
use App\Utils\Base64Helper;
use App\Utils\ErrorLogger;
use App\Utils\ConfigHelper;

class VideoService
{
    private array $config;
    private ErrorLogger $errorLogger;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->errorLogger = new ErrorLogger($config['storage']['log_dir']);
    }

    private function log(string $msg, $data = null): void
    {
        $milli = sprintf('%03d', (microtime(true) - floor(microtime(true))) * 1000);
        $timestamp = date('Y-m-d H:i:s') . '.' . $milli;
        $output = "[{$timestamp}] [VIDEO] {$msg}";
        if ($data !== null) {
            $output .= " | DATA: " . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
        }
        
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/video_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $output . PHP_EOL, FILE_APPEND);
    }

    public function generate(array $images, string $apiKey = '', string $model = '', array $options = []): array
    {
        $defaultProvider = $this->config['video']['default_provider'] ?? 'tongyi';
        $videoConfig = $this->config['video']['providers'][$defaultProvider] ?? [];
        
        $key = $apiKey ?: ConfigHelper::getApiKey($videoConfig);
        if (!$key) {
            throw new \RuntimeException("未找到视频生成 API Key，请检查 providers.php 配置文件中 video -> {$defaultProvider} -> api_key 字段。");
        }

        $resolvedModel = $model ?: ConfigHelper::getModel($videoConfig);
        $url = ConfigHelper::getBaseUrl($videoConfig);
        $prompt = $options['prompt'] ?? '';
        $generateAudio = $options['audio'] ?? false;
        $ratio = $options['ratio'] ?? '16:9';
        $duration = $options['duration'] ?? 6;
        $maxWait = $options['max_wait'] ?? 600;
        $productJson = $options['product_json'] ?? null;

        if (!$prompt && $productJson) {
            $prompt = $this->buildVideoPrompt(
                $productJson['product_description_for_prompt'] ?? '产品',
                $productJson['selling_points'] ?? []
            );
        }
        if (!$prompt) {
            $prompt = '高清电商产品视频，依次呈现商品全貌、材质细节、室内穿着效果，过渡自然流畅';
        }

        $input = ['prompt' => $prompt];
        if (!empty($images)) {
            // HappyHorse-1.0-i2v 必须使用 media 数组，且类型为 first_frame
            $input['media'] = [
                [
                    'type' => 'first_frame',
                    'url' => ImageHelper::toImageInput($images[0])
                ]
            ];
        }

        $duration = (int)$duration;
        if ($duration < 3) $duration = 3;
        if ($duration > 15) $duration = 15;

        $body = [
            'model' => $resolvedModel,
            'input' => $input,
            'parameters' => [
                'duration' => $duration,
                'resolution' => $options['resolution'] ?? '720P',
                'watermark' => false,
            ]
        ];

        $headers = [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
            'X-DashScope-Async' => 'enable'
        ];

        $this->log("POST REQUEST to {$url}", [
            'model' => $resolvedModel,
            'input' => array_merge($input, ['media' => '...base64...']), // 隐藏大段 base64
            'parameters' => $body['parameters']
        ]);

        $result = ImageHelper::httpPost($url, $headers, $body, 60);

        $this->log("POST RESPONSE from {$url}", $result);

        $taskId = $result['output']['task_id'] ?? '';
        if (!$taskId) {
            throw new \RuntimeException('视频任务提交失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        // 视频生成通常耗时 1-5 分钟，轮询间隔建议从 5s 开始，最大 30s
        $videoResult = $this->pollTask($key, $taskId, $maxWait);

        $videoUrl = $videoResult['output']['video_url'] ?? '';
        if (!$videoUrl) {
            throw new \RuntimeException('未找到视频URL: ' . json_encode($videoResult, JSON_UNESCAPED_UNICODE));
        }

        $outputDir = $this->config['storage']['output_dir'] . '/video';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $videoPath = $outputDir . '/product_video.mp4';
        $this->downloadVideo($videoUrl, $videoPath);

        return [
            'task_id' => $taskId,
            'video_path' => realpath($videoPath),
            'video_url' => $videoUrl,
        ];
    }

    private function buildVideoPrompt(string $productDesc, array $sellingPoints): string
    {
        $spList = array_map(function ($p) {
            return $p['zh_title'] ?? $p['zh'] ?? '';
        }, array_slice($sellingPoints, 0, 3));
        $sp = implode('、', array_filter($spList));

        return "高清电商产品视频，主体是 {$productDesc}。开头白底平铺缓慢旋转360°呈现细节 → 材质与做工近景突出质感 → 室内生活场景呈现产品穿着效果 → 多种家居场景自然切换，重点呈现卖点：{$sp}。镜头流畅自然，光线温暖明亮，节奏轻快现代，商业广告质感，8K品质，无水印、无文字叠加，产品所有细节完全一致，不变形、不走样。";
    }

    private function pollTask(string $key, string $taskId, int $maxWait = 600): array
    {
        $url = "https://dashscope.aliyuncs.com/api/v1/tasks/{$taskId}";
        $headers = ['Authorization' => "Bearer {$key}", 'Content-Type' => 'application/json'];
        $elapsed = 0;
        $interval = 5;

        while ($elapsed < $maxWait) {
            sleep($interval);
            $elapsed += $interval;

            $this->log("GET POLL REQUEST to {$url} (elapsed: {$elapsed}s)");
            $result = ImageHelper::httpGet($url, $headers, 30);
            $this->log("GET POLL RESPONSE from {$url}", $result);

            $status = $result['output']['task_status'] ?? '';

            if ($status === 'SUCCEEDED') {
                return $result;
            }
            if (in_array($status, ['FAILED', 'CANCELLED', 'UNKNOWN'])) {
                throw new \RuntimeException('视频生成失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            }

            $interval = min($interval + 5, 30);
        }

        throw new \RuntimeException("视频生成超时: task_id={$taskId}");
    }

    private function downloadVideo(string $url, string $path): void
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => 120],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $data = file_get_contents($url, false, $ctx);
        if ($data === false) {
            throw new \RuntimeException("下载视频失败: {$url}");
        }
        file_put_contents($path, $data);
    }
}
