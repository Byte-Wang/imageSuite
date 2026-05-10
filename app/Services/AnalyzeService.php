<?php

namespace App\Services;

use App\Utils\ImageHelper;
use App\Utils\Base64Helper;
use App\Utils\ConfigHelper;

class AnalyzeService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function analyze(array $imagePaths, string $provider = '', string $apiKey = '', string $baseUrl = '', string $model = '', string $lang = 'zh'): array
    {
        $providerConfig = $this->resolveProvider($provider, $apiKey);
        $providerInfo = $providerConfig['provider'];
        $key = $providerConfig['api_key'];

        $resolvedModel = $model ?: ConfigHelper::getModel($providerInfo);
        $resolvedUrl = $baseUrl ?: ConfigHelper::getBaseUrl($providerInfo);

        $prompt = $lang === 'zh' ? self::ANALYSIS_PROMPT_ZH : self::ANALYSIS_PROMPT_EN;

        $content = [];
        foreach ($imagePaths as $pathOrUrl) {
            $imageUrl = Base64Helper::toDataUri($pathOrUrl);
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $body = [
            'model' => $resolvedModel,
            'messages' => [['role' => 'user', 'content' => $content]],
            'max_tokens' => 2048,
            'temperature' => 0.1,
        ];

        $result = ImageHelper::httpPost($resolvedUrl . '/chat/completions', [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ], $body, 120);

        $raw = $result['choices'][0]['message']['content'] ?? '';
        if (!$raw) {
            return ['error' => '模型返回为空'];
        }

        return $this->extractJson($raw);
    }

    private function resolveProvider(string $provider, string $apiKey): array
    {
        $visionProviders = $this->config['vision']['providers'] ?? [];

        if ($provider && isset($visionProviders[$provider])) {
            $p = $visionProviders[$provider];
            $key = $apiKey ?: ConfigHelper::getApiKey($p);
            if (strlen($key) <= 5) {
                throw new \RuntimeException("供应商 '{$provider}' 未配置 API Key。请在配置文件的 'api_key' 字段填写。");
            }
            return ['provider' => $p, 'api_key' => $key];
        }

        foreach ($visionProviders as $p) {
            $key = ConfigHelper::getApiKey($p);
            if (strlen($key) > 5) {
                return ['provider' => $p, 'api_key' => $key];
            }
        }

        throw new \RuntimeException('未检测到任何视觉识别 API Key，请在 providers.php 配置文件的 "api_key" 字段填写。');
    }

    private function extractJson(string $text): array
    {
        $raw = trim($text);
        if (strpos($raw, '```json') !== false) {
            $raw = explode('```json', $raw, 2)[1];
        }
        if (strpos($raw, '```') !== false) {
            $raw = explode('```', $raw, 2)[0];
        }
        $raw = trim($raw);

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}') + 1;
        if ($start === false || $end === 0) {
            return ['error' => 'JSON 解析失败', 'raw' => $text];
        }

        $json = json_decode(substr($raw, $start, $end - $start), true);
        if ($json === null) {
            return ['error' => 'JSON 解析失败', 'raw' => $text];
        }

        return $json;
    }

    private const ANALYSIS_PROMPT_ZH = '你是一位拥有15年以上电商经验的顶级视觉分析师和爆款文案策划师。

请仔细观察图片中的商品，按以下 JSON 格式输出分析结果，只输出 JSON，不要输出其他内容：

```json
{
  "product_name": "商品详细名称，包含品类、材质、款型等关键词",
  "product_description_for_prompt": "英文描述，用于图像生成 Prompt，包含颜色/款型/印花/材质等视觉细节，50词以内",
  "product_type": "服装 | 3C数码 | 家居 | 美妆 | 食品 | 其他",
  "garment_position": "top | bottom | full-body | non-apparel（非服装统一填 non-apparel）",
  "visual_features": ["视觉特征1", "视觉特征2"],
  "selling_points": [
    {"icon": "fabric|fit|design|comfort|quality|function|scene", "zh": "中文卖点标题", "en": "English title", "zh_desc": "中文说明≤15字", "en_desc": "English desc ≤12 words", "visual_keywords": ["English keyword1", "English keyword2"]}
  ],
  "target_audience": "目标人群描述",
  "target_scenes": ["使用场景1", "使用场景2"],
  "product_style": "商品风格（如：法式浪漫 / 日系可爱 / 简约商务 / 运动休闲）",
  "color": "精确英文色值描述（如 pure white、lavender purple）",
  "material": "主要材质（若可识别）",
  "style": "版型描述（宽松oversized、修身等）",
  "print_design": "印花/设计描述",
  "print_design_lock": "精确约束短语，要求 exact same print pattern, color and position must not change",
  "product_name_zh": "中文商品名（简短版，用于文案叠加）"
}
```

selling_points 请提炼 3-5 条，优先级：材质 > 版型 > 设计感 > 舒适性 > 使用场景。从图片可见特征推断，不要凭空捏造。';

    private const ANALYSIS_PROMPT_EN = 'You are a top-tier e-commerce visual analyst and product marketing expert with 15+ years of experience.

Examine the product image carefully and output ONLY the following JSON, no other text:

```json
{
  "product_name": "Detailed product name with category, material, style keywords",
  "product_description_for_prompt": "English description for image generation prompt, include color/style/print/material visual details, within 50 words",
  "product_type": "Apparel | Electronics | Home | Beauty | Food | Other",
  "garment_position": "top | bottom | full-body | non-apparel (use non-apparel for all non-clothing products)",
  "visual_features": ["feature1", "feature2"],
  "selling_points": [
    {"icon": "fabric|fit|design|comfort|quality|function|scene", "zh": "Chinese title", "en": "English title", "zh_desc": "Chinese desc ≤15 chars", "en_desc": "English desc ≤12 words", "visual_keywords": ["English keyword1", "English keyword2"]}
  ],
  "target_audience": "Target audience description",
  "target_scenes": ["scene1", "scene2"],
  "product_style": "Product style (e.g. Romantic French / Casual Sporty / Minimalist Office)",
  "color": "Precise color (e.g. pure white, lavender purple)",
  "material": "Main material (if identifiable)",
  "style": "Fit description (oversized, slim-fit, etc.)",
  "print_design": "Print/design description",
  "print_design_lock": "Exact constraint phrase with: exact same print pattern, color and position must not change",
  "product_name_zh": "Short Chinese product name for overlay text"
}
```

Provide 3-5 selling_points, priority: material > fit > design > comfort > scene. Derived from visible features only.';
}
