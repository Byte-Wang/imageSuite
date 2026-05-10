<?php

return [
    // ========================================================
    // 视觉分析配置（用于商品图片分析）
    // ========================================================
    'vision' => [
        'default_provider' => 'tongyi', // 默认供应商
        'providers' => [
            'tongyi' => [
                'name' => '阿里云通义千问 VL',
                'company' => '阿里云 DashScope',
                // ┌──────────────────────────────────────────┐
                // │  建议：直接在这里填写 Key，优先使用      │
                // └──────────────────────────────────────────┘
                'api_key' => '', // 直接填写：sk-xxxxxxxxxx
                'base_url' => '', // 直接填写 API 地址（可选）
                'model' => 'qwen3.6-flash-2026-04-16', // 直接填写模型名称（可选）

                // ┌──────────────────────────────────────────┐
                // │  备用：从环境变量读取（向后兼容）        │
                // └──────────────────────────────────────────┘
                'env_key' => 'DASHSCOPE_API_KEY', // 环境变量名
                'env_key_alt' => 'ALIYUN_API_KEY', // 备用环境变量名（兼容不同配置习惯）
                'env_url' => 'DASHSCOPE_BASE_URL', // API 地址环境变量名
                'env_model' => 'DASHSCOPE_VISION_MODEL', // 模型环境变量名

                // 当以上都没有配置时，使用这些默认值
                'default_model' => 'qwen3.6-flash-2026-04-16',
                'default_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
            ],
            'doubao' => [
                'name' => '字节豆包视觉',
                'company' => '字节跳动',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'ARK_API_KEY',
                'env_key_alt' => '',
                'env_url' => 'ARK_BASE_URL',
                'env_model' => 'ARK_VISION_MODEL',
                'default_model' => 'doubao-vision-pro-32k',
                'default_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            ],
            'openai' => [
                'name' => 'OpenAI GPT-4o',
                'company' => 'OpenAI',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'OPENAI_API_KEY',
                'env_key_alt' => '',
                'env_url' => 'OPENAI_BASE_URL',
                'env_model' => 'OPENAI_VISION_MODEL',
                'default_model' => 'gpt-4o',
                'default_url' => 'https://api.openai.com/v1',
            ],
        ],
    ],

    // ========================================================
    // 图像生成配置（用于生成电商图片）
    // ========================================================
    'image' => [
        'default_provider' => 'tongyi',
        'providers' => [
            'openai' => [
                'name' => 'DALL·E 3 / GPT Image',
                'company' => 'OpenAI',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'OPENAI_API_KEY',
                'env_url' => 'OPENAI_BASE_URL',
                'env_model' => 'OPENAI_MODEL',
                'default_model' => '',
                'default_url' => 'https://api.openai.com/v1/images/generations',
                'supports_reference' => true, // 是否支持参考图
                'reference_models' => [],
            ],
            'gemini' => [
                'name' => 'Gemini Imagen 3',
                'company' => 'Google',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'GEMINI_API_KEY',
                'env_url' => 'GEMINI_BASE_URL',
                'env_model' => 'GEMINI_MODEL',
                'default_model' => 'gemini-3.1-flash-image-preview',
                'default_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image-preview:generateContent',
                'supports_reference' => true,
            ],
            'stability' => [
                'name' => 'Stable Image Core',
                'company' => 'Stability AI',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'STABILITY_API_KEY',
                'env_url' => 'STABILITY_BASE_URL',
                'env_model' => 'STABILITY_MODEL',
                'default_model' => 'core',
                'default_url' => 'https://api.stability.ai/v2beta/stable-image/generate/core',
                'supports_reference' => false,
            ],
            'tongyi' => [
                'name' => '千问',
                'company' => '阿里云 DashScope',
                'api_key' => '', 
                'base_url' => '',
                'model' => '',
                'env_key' => 'DASHSCOPE_API_KEY',
                'env_url' => 'DASHSCOPE_BASE_URL',
                'env_model' => 'DASHSCOPE_MODEL',
                'default_model' => 'qwen-image-2.0',
                'default_url' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
                'supports_reference' => true,
                'reference_models' => ['qwen-image-2.0', 'qwen-image-2.0-pro','qwen-image-2.0-pro-2026-04-22', 'wan2.7-i2v', 'wan2.7-i2v-2026-04-25'],
            ],
            'doubao' => [
                'name' => '豆包 Seedream',
                'company' => '字节跳动',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'ARK_API_KEY',
                'env_url' => 'ARK_BASE_URL',
                'env_model' => 'ARK_IMAGE_MODEL',
                'default_model' => 'doubao-seedream-4-5-251128',
                'default_url' => 'https://ark.cn-beijing.volces.com/api/v3/images/generations',
                'supports_reference' => true,
            ],
        ],
    ],

    // ========================================================
    // 视频生成配置
    // ========================================================
    'video' => [
        'default_provider' => 'tongyi',
        'providers' => [
            'tongyi' => [
                'name' => '千问',
                'company' => '阿里',
                'api_key' => '', // 直接填写
                'base_url' => '',
                'model' => '',
                'env_key' => 'DASHSCOPE_API_KEY',
                'env_url' => 'DASHSCOPE_BASE_URL',
                'env_model' => 'DASHSCOPE_VIDEO_MODEL',
                'default_model' => 'happyhorse-1.0-i2v',
                'default_url' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis',
                'supports_reference' => true,
            ],
        ],
    ],
];
