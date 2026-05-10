<?php

namespace App\Services;

class PromptBuilder
{
    public const TYPE_NAMES_ZH = [
        'white_bg' => '白底主图',
        'key_features' => '核心卖点图',
        'selling_pt' => '卖点图',
        'material' => '材质图',
        'lifestyle' => '场景展示图',
        'model' => '模特展示图',
        'multi_scene' => '多场景拼图',
        'ecommerce_detail' => '电商详情图',
        'three_angle_view' => '三角度拼图',
    ];

    private const TYPE_IMAGE_SLOT = [
        'white_bg' => 0,
        'key_features' => 0,
        'selling_pt' => 0,
        'material' => 1,
        'lifestyle' => 0,
        'model' => 0,
        'multi_scene' => 0,
        'ecommerce_detail' => 0,
        'three_angle_view' => 0,
    ];

    private const INPUT_TYPE_COMPOSITIONS = [
        'flat_lay' => 'displayed flat on pure white background (RGB 255,255,255), front view, slight 5-degree product tilt, natural fabric drape, subtle soft shadow beneath',
        'flat_lay_front_back' => 'displayed flat on pure white background (RGB 255,255,255), front view, slight 5-degree product tilt, natural fabric drape, subtle soft shadow beneath, showcasing the full front design and silhouette',
        'hanging' => 'hanging naturally on an invisible hook on pure white background (RGB 255,255,255), full length, front view, fabric draping naturally',
        'hanging_front_back' => 'hanging naturally on an invisible hook on pure white background (RGB 255,255,255), full length, front view, fabric draping naturally',
        'model' => 'product isolated on pure white background (RGB 255,255,255), front view, product shape preserved, no model',
    ];

    public function buildPrompt(string $typeId, string $desc, array $sellingPoints, array $options = []): string
    {
        $lang = $options['lang'] ?? 'zh';
        $modelStyle = $options['model_style'] ?? 'standard';
        $hasModelRef = $options['has_model_ref'] ?? false;
        $garmentPosition = $options['garment_position'] ?? 'non-apparel';
        $printDesignLock = $options['print_design_lock'] ?? '';
        $hasProductRef = $options['has_product_ref'] ?? false;
        $inputImageType = $options['input_image_type'] ?? 'flat_lay';
        $templateSet = $options['template_set'] ?? 1;
        $keyFeaturesStyle = $options['key_features_style'] ?? '';
        $perTypeTemplates = $options['per_type_templates'] ?? [];
        $targetScenes = $options['target_scenes'] ?? [];
        $productStyle = $options['product_style'] ?? '';
        $targetAudience = $options['target_audience'] ?? '';
        $targetSceneEnvs = $options['target_scene_envs'] ?? [];
        $productType = $options['product_type'] ?? '';
        $modelEthnicity = $options['model_ethnicity'] ?? 'asian';

        $isApparel = $garmentPosition !== 'non-apparel';
        $sp = $sellingPoints;
        $modelSubject = $this->inferModelSubject($targetAudience, $modelEthnicity);
        $pairing = $this->inferPairing($garmentPosition);
        $outfit = $isApparel ? "wearing {$desc} {$pairing}" : "showcasing {$desc}";
        $outfit = rtrim($outfit);

        $QUALITY = "shot on Sony A7R V, 85mm f/2.0 lens, natural diffused studio lighting, authentic commercial product photography, true-to-life colors no heavy post-processing, realistic fabric texture and natural drape, professional e-commerce visual style. CRITICAL: Keep the EXACT same product design, color, print, proportions and all details. Do NOT alter any design element. Do NOT redesign, recolor, replace, or rotate the product into a different structure.";

        $PRODUCT_REF_LOCK = "CRITICAL HIGHEST PRIORITY: Product reference image is provided. You MUST use the reference image as the EXACT basis for the product. Keep EXACT same: silhouette, print pattern, print position, all colors, neckline, sleeves, hem, fabric texture. DO NOT change: the print design, color scheme, silhouette shape, fabric appearance. You may ONLY change: background scene, camera angle, lighting, model pose. The product must look IDENTICAL to the reference image - same dress, same pattern, same colors.";

        $TEXT_RENDER = $lang === 'zh'
            ? "EXTREMELY IMPORTANT: Render ALL text in Simplified Chinese ONLY. Use clean modern bold sans-serif typography (思源黑体 / Alibaba PuHuiTi or similar). Text must be perfectly sharp, highly legible, excellent hierarchy, proper kerning. Use subtle drop shadow (black 30% opacity) for readability. Professional commercial layout, balanced spacing, no distortion, no overlapping."
            : "EXTREMELY IMPORTANT: Render ALL text in English ONLY. Use clean modern bold sans-serif typography (Helvetica Neue, Arial, or similar). Text must be perfectly sharp, highly legible, excellent hierarchy, proper kerning. Use subtle drop shadow (black 30% opacity) for readability. Professional commercial layout, balanced spacing, no distortion, no overlapping.";

        $lockTail = $printDesignLock ? " {$printDesignLock}" : '';
        $refTail = $hasProductRef ? " {$PRODUCT_REF_LOCK}" : '';

        $kfHeading = $lang === 'zh' ? '为什么选择我们' : 'WHY CHOOSE US';
        $kfLabels = [
            $this->spTitle($sp, 0, $lang),
            $this->spTitle($sp, 1, $lang),
            $this->spTitle($sp, 2, $lang),
        ];

        $spHeading = $this->spTitle($sp, 1, $lang) ?: $this->spTitle($sp, 0, $lang);
        $spSub1 = $this->spDesc($sp, 1, $lang) ?: $this->spDesc($sp, 0, $lang);
        $spSub2 = $this->spDesc($sp, 2, $lang) ?: $this->spDesc($sp, 1, $lang);

        $matHeading = $this->spTitle($sp, 0, $lang);
        $matSub1 = $this->spDesc($sp, 0, $lang);
        $matSub2 = $this->spDesc($sp, 2, $lang) ?: $this->spDesc($sp, 1, $lang);

        $ts = array_filter($targetScenes);
        $lsHeading = ($productStyle ? mb_substr($productStyle, 0, 20) : null)
            ?: $this->spTitle($sp, 2, $lang)
            ?: $this->spTitle($sp, 0, $lang)
            ?: ($lang === 'zh' ? '多场景百搭' : 'VERSATILE EVERYDAY STYLE');
        $lsSub1 = ($ts[0] ?? '') ? mb_substr($ts[0], 0, 15) : ($this->spTitle($sp, 0, $lang) ?: ($lang === 'zh' ? '精选面料' : 'Premium Quality'));
        $lsSub2 = ($ts[1] ?? '') ? mb_substr($ts[1], 0, 15) : ($this->spTitle($sp, 1, $lang) ?: ($lang === 'zh' ? '品质设计' : 'Elegant Design'));

        $MODEL_REF_LOCK = "CRITICAL: Two reference images are provided. First image: Model reference — MUST use EXACTLY the same {$modelSubject}: identical face, skin tone, hair, body shape, expression and ethnicity. Do not replace or change the model. Second image: Product reference — MUST use EXACTLY the same garment design: silhouette, print pattern, print position, all colors, neckline, sleeves, hem, fabric texture. The model must WEAR the EXACT SAME garment from the second image. Do NOT change the garment design, color, or style.";
        $spModelLock = $hasModelRef ? " {$MODEL_REF_LOCK}" : '';
        $MODEL_REALISM = ' natural hair flyaway, subtle hand movement, authentic candid posture.';

        $whiteBgComposition = self::INPUT_TYPE_COMPOSITIONS[$inputImageType] ?? 'centered on pure white background, front 3/4 view, slight angle, subtle shadow beneath, 88% frame';
        $materialView = in_array($inputImageType, ['flat_lay_front_back', 'hanging_front_back']) ? 'back surface, showing reverse-side fabric detail' : 'surface detail';

        $kfDetailA = $this->spVisualDetail($sp, 0) ?: 'fabric texture and stitching';
        $kfDetailB = $this->spVisualDetail($sp, 1) ?: 'design detail and craftsmanship';
        $kfDetailC = $this->spVisualDetail($sp, 2) ?: 'silhouette and fit';

        $kfStyle = $keyFeaturesStyle ?: $this->resolveKfStyle($perTypeTemplates, $isApparel);

        return match ($typeId) {
            'white_bg' => $this->buildWhiteBg($desc, $whiteBgComposition, $refTail, $QUALITY),
            'key_features' => $this->buildKeyFeatures($desc, $kfStyle, $kfHeading, $kfLabels, $kfDetailA, $kfDetailB, $kfDetailC, $TEXT_RENDER, $refTail, $QUALITY),
            'selling_pt' => $this->buildSellingPt($desc, $spHeading, $spSub1, $spSub2, $isApparel, $templateSet, $TEXT_RENDER, $refTail, $QUALITY, $targetSceneEnvs, $targetScenes, $productType),
            'material' => $this->buildMaterial($desc, $matHeading, $matSub1, $matSub2, $isApparel, $templateSet, $materialView, $TEXT_RENDER, $refTail, $QUALITY),
            'lifestyle' => $this->buildLifestyle($desc, $lsHeading, $lsSub1, $lsSub2, $isApparel, $templateSet, $modelSubject, $outfit, $TEXT_RENDER, $QUALITY, $lockTail, $refTail, $spModelLock, $targetSceneEnvs, $targetScenes, $productType),
            'model' => $this->buildModel($desc, $modelStyle, $templateSet, $modelSubject, $outfit, $isApparel, $refTail, $QUALITY, $MODEL_REALISM, $spModelLock, $targetSceneEnvs, $targetScenes, $productType),
            'multi_scene' => $this->buildMultiScene($desc, $isApparel, $templateSet, $modelSubject, $outfit, $sp, $lang, $ts, $targetSceneEnvs, $targetScenes, $productType, $TEXT_RENDER, $QUALITY, $lockTail, $refTail, $spModelLock),
            'ecommerce_detail' => $this->buildEcommerceDetail($desc, $sp, $lang, $ts, $kfLabels, $TEXT_RENDER, $refTail, $QUALITY),
            'three_angle_view' => $this->buildThreeAngleView($desc, $isApparel, $modelSubject, $outfit, $QUALITY, $lockTail, $refTail, $spModelLock),
            default => "{$desc}. {$QUALITY}",
        };
    }

    public function getImageSlot(string $typeId): int
    {
        return self::TYPE_IMAGE_SLOT[$typeId] ?? 0;
    }

    private function spTitle(array $sp, int $i, string $lang): string
    {
        $pt = $sp[$i] ?? null;
        if (!$pt) return '';
        return $lang === 'zh'
            ? ($pt['zh_title'] ?? $pt['zh'] ?? '')
            : ($pt['en_title'] ?? $pt['en'] ?? '');
    }

    private function spDesc(array $sp, int $i, string $lang): string
    {
        $pt = $sp[$i] ?? null;
        if (!$pt) return '';
        return $lang === 'zh'
            ? ($pt['zh_desc'] ?? $pt['description'] ?? $pt['zh'] ?? '')
            : ($pt['en_desc'] ?? $pt['en'] ?? '');
    }

    private function spVisualDetail(array $sp, int $i): string
    {
        $pt = $sp[$i] ?? null;
        if (!$pt) return '';
        $kw = $pt['visual_keywords'] ?? [];
        if (!empty($kw)) {
            return implode(', ', array_slice(array_map('strval', $kw), 0, 2));
        }
        return $pt['en_desc'] ?? $pt['en'] ?? '';
    }

    private function inferPairing(string $garmentPosition): string
    {
        return match ($garmentPosition) {
            'top' => 'paired with light blue denim shorts',
            'bottom' => 'paired with a simple white T-shirt',
            default => '',
        };
    }

    private function inferModelSubject(string $targetAudience, string $modelEthnicity = 'asian'): string
    {
        $s = strtolower($targetAudience);
        $gender = (preg_match('/男|男士|男性|men|male|boy/', $s)) ? 'male' : 'female';
        $isChild = (preg_match('/儿童|小孩|宝宝|孩子|children|child|kids/', $s));

        $ethnicity = strtolower($modelEthnicity);
        $race = match ($ethnicity) {
            'western' => 'Caucasian',
            'mixed' => 'ethnically diverse',
            default => 'Asian',
        };

        if ($isChild) {
            return "{$race} child model aged 6-12";
        }
        return "{$race} {$gender} model";
    }

    private function sceneToEnv(string $sceneZh, string $productType = ''): string
    {
        $s = $sceneZh;
        $mappings = [
            ['居家|睡|卧室|内衣|睡衣|室内|家中|闺蜜', 'cozy bedroom interior, soft ambient lamp light, satin bedding, intimate home setting'],
            ['海边|沙滩|度假|海滩|海岛', 'tropical beach, golden sand, turquoise ocean backdrop, sunlit coastal scene'],
            ['约会|浪漫|晚宴|romantic|date|情侣', 'intimate romantic restaurant terrace, warm candlelight, evening ambient glow'],
            ['运动|健身|瑜伽|跑步|gym|sport', 'modern fitness studio or park path, natural light, clean athletic atmosphere'],
            ['派对|聚会|party|gathering|社交', 'chic social venue, warm ambient lighting, stylish gathering atmosphere'],
            ['办公|通勤|上班|商务|office|work', 'modern office or minimalist café workspace, clean professional atmosphere'],
            ['校园|学校|课堂|campus|school', 'sunny campus green lawn or café, shallow depth of field, youthful atmosphere'],
            ['户外|公园|街头|城市|散步|逛街|出行', 'lush outdoor park or urban street, golden hour natural light, soft bokeh'],
            ['旅行|出游|旅游|travel|trip', 'scenic travel destination, open air, natural bright light, wanderlust vibe'],
            ['咖啡|下午茶|café|coffee', 'cozy café interior, wooden table, warm natural window light, lifestyle mood'],
            ['客厅|沙发|起居|living room', 'bright modern living room, clean sofa and wooden floor, warm natural light'],
            ['厨房|餐厅|烹饪|kitchen', 'clean modern kitchen counter, marble surface, soft overhead lighting'],
            ['书房|书桌|学习|阅读|study|desk', 'tidy home study room, wooden desk, soft warm desk lamp, bookshelf background'],
            ['办公桌|工作台|workspace', 'minimalist office desk setup, clean workspace, natural window light'],
            ['床头|睡前|bedside', 'serene bedroom setting, bedside table, soft ambient lamp glow'],
            ['清洁|打扫|吸尘|拖地|clean', 'bright clean home interior, polished floor, natural daylight, tidying scene'],
            ['装修|家装|布置|decor|interior', 'stylish home interior during decoration, warm ambient light, modern furniture'],
            ['庭院|花园|园艺|garden|outdoor work|户外劳作', 'sunlit backyard garden, lush green plants, natural bright outdoor daylight'],
        ];

        foreach ($mappings as [$keywords, $env]) {
            if (preg_match("/{$keywords}/u", $s)) {
                return $env;
            }
        }

        $pt = strtolower($productType);
        if (preg_match('/家居|home|furniture|家具/u', $pt)) return 'bright Scandinavian-style living room, clean surfaces, warm natural light';
        if (preg_match('/3c|数码|电器|electronics|appliance/u', $pt)) return 'clean minimalist workspace or kitchen counter, soft studio-style lighting';
        if (preg_match('/美妆|beauty|cosmetic|护肤/u', $pt)) return 'elegant vanity desk, soft diffused light, fresh white background, beauty mood';
        if (preg_match('/食品|food|零食|beverage/u', $pt)) return 'warm kitchen countertop with natural ingredients, rustic wooden surface, fresh daylight';

        return 'bright authentic lifestyle setting, natural light, shallow depth of field';
    }

    private function getSceneEnv(array $targetSceneEnvs, int $index, array $targetScenes, string $productType = ''): string
    {
        if (!empty($targetSceneEnvs) && $index < count($targetSceneEnvs)) {
            $env = trim($targetSceneEnvs[$index] ?? '');
            if ($env) return $env;
        }
        if (!empty($targetScenes) && $index < count($targetScenes)) {
            return $this->sceneToEnv($targetScenes[$index], $productType);
        }
        return $this->sceneToEnv('', $productType);
    }

    private function resolveKfStyle(array $perTypeTemplates, bool $isApparel): string
    {
        if (isset($perTypeTemplates['key_features'])) {
            $tpl = $perTypeTemplates['key_features'];
            return match ($tpl) {
                2 => 'annotation',
                3 => 'split',
                4 => 'badge',
                5 => 'gold_bubble',
                default => $isApparel ? 'magnifier' : 'icon_list',
            };
        }
        return $isApparel ? 'magnifier' : 'icon_list';
    }

    private function buildWhiteBg(string $desc, string $composition, string $refTail, string $QUALITY): string
    {
        return "{$desc}, {$composition}, product occupies 88% of frame, clean studio lighting with soft shadow. No text.{$refTail} {$QUALITY}";
    }

    private function buildKeyFeatures(string $desc, string $kfStyle, string $kfHeading, array $kfLabels, string $kfDetailA, string $kfDetailB, string $kfDetailC, string $TEXT_RENDER, string $refTail, string $QUALITY): string
    {
        return match ($kfStyle) {
            'icon_list' => "Modern minimalist infographic, light gray gradient bg. Left: {$desc} front view (45%). Right: bold heading \"{$kfHeading}\", three vertical icon+text: \"{$kfLabels[0]}\", \"{$kfLabels[1]}\", \"{$kfLabels[2]}\". Premium layout. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            'annotation' => "{$desc}, editorial product photography, product centered on warm beige background. Three elegant handwritten-style annotation lines from product details: annotation 1 → \"{$kfLabels[0]}\" ({$kfDetailA}); annotation 2 → \"{$kfLabels[1]}\" ({$kfDetailB}); annotation 3 → \"{$kfLabels[2]}\" ({$kfDetailC}). Kinfolk magazine aesthetic, natural light and shadows, serif typeface. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            'split' => "{$desc}, ultra-minimalist product photography on pure black background. Product in white spotlight center, single white hairline border frame. Three feature labels in clean white sans-serif: \"{$kfLabels[0]}\", \"{$kfLabels[1]}\", \"{$kfLabels[2]}\". Luxury fashion brand, zero clutter, monochrome palette. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            'badge' => "{$desc}, high-energy commercial product photography, white background. Bold sunburst starburst in yellow (#FFD700) behind product. Three circular badge labels in vivid red (#E02E24), extra-bold font tilted -3deg: \"{$kfLabels[0]}!\", \"{$kfLabels[1]}!\", \"{$kfLabels[2]}!\". Explosive high-saturation POP art energy. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            'gold_bubble' => "{$desc}, luxury dark product photography on deep charcoal (#1A1A2E) background. Product lit with golden side light. Three gold-bordered circular callout bubbles with feature labels: \"{$kfLabels[0]}\", \"{$kfLabels[1]}\", \"{$kfLabels[2]}\". Premium fashion editorial dark aesthetic, gold accent (#C8A86C). {$TEXT_RENDER}{$refTail} {$QUALITY}",
            default => "{$desc}, high-end product photography, centered floating composition, clean softly blurred background; featuring 3 circular magnifying glass insets (callout bubbles) connected by thin elegant lines to specific parts of the main product: Inset 1 (top-left): close-up of [{$kfDetailA}], label \"{$kfLabels[0]}\"; Inset 2 (top-right): close-up of [{$kfDetailB}], label \"{$kfLabels[1]}\"; Inset 3 (bottom-right): close-up of [{$kfDetailC}], label \"{$kfLabels[2]}\". Soft studio lighting, minimalist commercial design, sharp focus on product, bokeh background. {$TEXT_RENDER}{$refTail} {$QUALITY}",
        };
    }

    private function buildSellingPt(string $desc, string $spHeading, string $spSub1, string $spSub2, bool $isApparel, int $templateSet, string $TEXT_RENDER, string $refTail, string $QUALITY, array $targetSceneEnvs, array $targetScenes, string $productType): string
    {
        $spEnv = ucfirst($this->getSceneEnv($targetSceneEnvs, 0, $targetScenes, $productType));

        return match ($templateSet) {
            2 => $isApparel
                ? "Bright café window seat, morning natural light, warm wooden surface. {$desc} casually arranged in lifestyle context. Bold heading \"{$spHeading}\" upper left, \"{$spSub1}\", \"{$spSub2}\". Kinfolk lifestyle mood. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Bright café window seat, morning natural light, warm wooden surface. {$desc} placed as a lifestyle product hero. Bold heading \"{$spHeading}\" upper left, \"{$spSub1}\", \"{$spSub2}\". Kinfolk product mood. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            3 => $isApparel
                ? "Pure white minimal studio, crisp shadows. {$desc} centered on white surface. Single bold heading \"{$spHeading}\" top, \"{$spSub1}\", \"{$spSub2}\" below. No props, zero clutter. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Pure white minimal studio, crisp directional shadows. {$desc} centered on white surface, product as hero object. Single bold heading \"{$spHeading}\" top, \"{$spSub1}\", \"{$spSub2}\" below. No props, zero clutter. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            4 => $isApparel
                ? "Vibrant outdoor urban street, high saturation colors, dynamic energy. {$desc} featured prominently. Bold heading \"{$spHeading}\", \"{$spSub1}\", \"{$spSub2}\". Pop art energy. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Vibrant high-energy commercial setting, high saturation colors. {$desc} showcased boldly as hero product. Bold heading \"{$spHeading}\", \"{$spSub1}\", \"{$spSub2}\". Pop art energy. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            5 => $isApparel
                ? "Dark atmospheric studio, single beam spotlight illuminating {$desc}. Deep moody shadows, cinematic feel. Bold gold heading \"{$spHeading}\", \"{$spSub1}\", \"{$spSub2}\" in white. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Dark atmospheric studio, single beam spotlight illuminating {$desc} product. Product surface details highlighted, deep cinematic shadows. Bold gold heading \"{$spHeading}\", \"{$spSub1}\", \"{$spSub2}\" in white. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            default => $isApparel
                ? "{$spEnv}, warm natural light. {$desc} worn with relaxed natural pose. Bold heading \"{$spHeading}\" upper left, two lines: \"{$spSub1}\", \"{$spSub2}\". Commercial lifestyle mood. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "{$spEnv}, clean natural light. {$desc} displayed prominently as hero product. Bold heading \"{$spHeading}\" upper left, two lines: \"{$spSub1}\", \"{$spSub2}\". Commercial product mood. {$TEXT_RENDER}{$refTail} {$QUALITY}",
        };
    }

    private function buildMaterial(string $desc, string $matHeading, string $matSub1, string $matSub2, bool $isApparel, int $templateSet, string $materialView, string $TEXT_RENDER, string $refTail, string $QUALITY): string
    {
        return match ($templateSet) {
            2 => $isApparel
                ? "{$desc} laid flat on natural oak wood or white marble surface, editorial flat lay, top-down bird's eye view, warm morning light. Bold heading \"{$matHeading}\" upper right, \"{$matSub1}\" mid, \"{$matSub2}\" lower. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "{$desc} placed on natural oak wood or white marble surface, editorial top-down product shot, warm morning light, clean composition. Bold heading \"{$matHeading}\" upper right, \"{$matSub1}\" mid, \"{$matSub2}\" lower. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            3 => $isApparel
                ? "{$desc} neatly folded in geometric layers on pure white surface, crisp shadows emphasizing fold lines and fabric weight. Single bold heading \"{$matHeading}\" right, \"{$matSub1}\", \"{$matSub2}\". Architectural minimal aesthetic. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "{$desc} precisely arranged on pure white surface, crisp directional shadows emphasizing product geometry and construction. Single bold heading \"{$matHeading}\" right, \"{$matSub1}\", \"{$matSub2}\". Architectural minimal aesthetic. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            4 => $isApparel
                ? "{$desc} dramatically unfolded showing vivid fabric layers at dynamic angle, high saturation colors, close-up angled shot. Bold heading \"{$matHeading}\" corner, \"{$matSub1}\", \"{$matSub2}\" in red. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "{$desc} showcased at a dynamic dramatic angle, bold close-up highlighting surface quality, high saturation colors, energetic composition. Bold heading \"{$matHeading}\" corner, \"{$matSub1}\", \"{$matSub2}\" in red. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            5 => $isApparel
                ? "Extreme close-up of {$desc} fabric ({$materialView}) against deep black background, golden rim lighting tracing fabric edge, dramatic contrast. Bold gold heading \"{$matHeading}\" right, \"{$matSub1}\", \"{$matSub2}\" in white. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Extreme close-up of {$desc} surface finish and construction details against deep black background, golden rim lighting tracing product edges, dramatic contrast. Bold gold heading \"{$matHeading}\" right, \"{$matSub1}\", \"{$matSub2}\" in white. {$TEXT_RENDER}{$refTail} {$QUALITY}",
            default => $isApparel
                ? "Extreme macro fabric texture of {$desc} ({$materialView}), dramatic side lighting, soft folds. Blurred natural background. Bold heading \"{$matHeading}\" upper right, \"{$matSub1}\" mid, \"{$matSub2}\" lower. Hyper detailed. {$TEXT_RENDER}{$refTail} {$QUALITY}"
                : "Extreme close-up product detail shot of {$desc}, showcasing surface finish, construction quality and material texture. Dramatic side lighting on {$materialView}, clean neutral background. Bold heading \"{$matHeading}\" upper right, \"{$matSub1}\" mid, \"{$matSub2}\" lower. Hyper detailed product photography. {$TEXT_RENDER}{$refTail} {$QUALITY}",
        };
    }

    private function buildLifestyle(string $desc, string $lsHeading, string $lsSub1, string $lsSub2, bool $isApparel, int $templateSet, string $modelSubject, string $outfit, string $TEXT_RENDER, string $QUALITY, string $lockTail, string $refTail, string $spModelLock, array $targetSceneEnvs, array $targetScenes, string $productType): string
    {
        $env1 = ucfirst($this->getSceneEnv($targetSceneEnvs, 0, $targetScenes, $productType));

        return match ($templateSet) {
            2 => $isApparel
                ? "{$env1}, natural golden light, soft bokeh. {$modelSubject} {$outfit}, relaxed natural pose, the {$desc} is the visual focus. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". Magazine editorial warmth. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "{$env1}, warm natural light. {$desc} placed naturally as the visual focus. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            3 => $isApparel
                ? "Clean minimal interior space, soft diffused natural light, architectural simplicity. {$modelSubject} {$outfit}, simple elegant pose, the {$desc} is the visual focus. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". Minimal luxury feel. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "Clean minimal interior, white surfaces, minimal props. {$desc} placed as hero object. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            4 => $isApparel
                ? "Vibrant dynamic scene with saturated colors, energetic atmosphere. {$modelSubject} {$outfit}, energetic pose, the {$desc} pops with color. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". Street fashion energy. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "Vibrant colorful lifestyle context, high energy atmosphere. {$desc} featured boldly as the visual focus. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            5 => $isApparel
                ? "Moody atmospheric scene, dramatic low-key lighting, deep shadows. {$modelSubject} {$outfit}, cool editorial pose, the {$desc} is the visual focus. Bold heading \"{$lsHeading}\" upper left in gold, \"{$lsSub1}\" and \"{$lsSub2}\" in white. Night fashion mood. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "Moody dark atmospheric scene, cinematic low-key lighting. {$desc} featured in atmospheric dark context. Bold heading \"{$lsHeading}\" upper left, \"{$lsSub1}\" and \"{$lsSub2}\". {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            default => $isApparel
                ? "{$env1}, warm natural light, shallow DOF. {$modelSubject} {$outfit}, the {$desc} is the absolute visual focus. Bold white heading \"{$lsHeading}\" upper left with shadow, \"{$lsSub1}\" and \"{$lsSub2}\" lower left. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "{$env1}, natural light, shallow DOF. {$desc} placed prominently as the visual focus. Bold white heading \"{$lsHeading}\" upper left with shadow, \"{$lsSub1}\" and \"{$lsSub2}\" lower left. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
        };
    }

    private function buildModel(string $desc, string $modelStyle, int $templateSet, string $modelSubject, string $outfit, bool $isApparel, string $refTail, string $QUALITY, string $MODEL_REALISM, string $spModelLock, array $targetSceneEnvs, array $targetScenes, string $productType): string
    {
        if ($modelStyle === 'bodycon') {
            return "Full-body studio fashion shot, clean solid background. {$modelSubject} wearing {$outfit}. Fitted silhouette showing garment shape. Professional commercial lighting. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}";
        }

        $modelEnv = ucfirst($this->getSceneEnv($targetSceneEnvs, 0, $targetScenes, $productType));

        return match ($templateSet) {
            2 => "Bright café interior with window. {$modelSubject} sitting {$outfit}. Warm natural light, casual pose. The {$desc} is clearly visible. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}",
            3 => "Clean white seamless studio background. {$modelSubject} full body standing {$outfit}. Even professional lighting. The {$desc} is the focus. Minimalist commercial style. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}",
            4 => "Modern city street scene. {$modelSubject} walking {$outfit}. Dynamic outdoor setting with natural lighting. The {$desc} is clearly visible. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}",
            5 => "Professional dark studio with focused lighting. {$modelSubject} standing {$outfit}. Single light source, clean dark background. The {$desc} is clearly visible. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}",
            default => "{$modelEnv}. {$modelSubject} wearing {$outfit}. The {$desc} is clearly visible. Natural professional lighting. No text.{$refTail} {$QUALITY}{$MODEL_REALISM}{$spModelLock}",
        };
    }

    private function buildMultiScene(string $desc, bool $isApparel, int $templateSet, string $modelSubject, string $outfit, array $sp, string $lang, array $ts, array $targetSceneEnvs, array $targetScenes, string $productType, string $TEXT_RENDER, string $QUALITY, string $lockTail, string $refTail, string $spModelLock): string
    {
        $msHeading = $this->spTitle($sp, 2, $lang) ?: ($lang === 'zh' ? '一件多穿，随心切换' : 'VERSATILE FOR ANY OCCASION');
        $msLeft = ($ts[0] ?? '') ? mb_substr($ts[0], 0, 12) : ($lang === 'zh' ? '居家休闲' : 'Home Casual');
        $msRight = ($ts[1] ?? '') ? mb_substr($ts[1], 0, 12) : ($lang === 'zh' ? '日常出行' : 'Daily Lifestyle');

        $s1 = $this->getSceneEnv($targetSceneEnvs, 0, $targetScenes, $productType);
        $s2 = $this->getSceneEnv($targetSceneEnvs, 1, $targetScenes, $productType);
        $s3 = $this->getSceneEnv($targetSceneEnvs, 2, $targetScenes, $productType);
        $s1Label = ($ts[0] ?? '') ? mb_substr($ts[0], 0, 12) : $msLeft;
        $s2Label = ($ts[1] ?? '') ? mb_substr($ts[1], 0, 12) : $msRight;
        $s3Label = ($ts[2] ?? '') ? mb_substr($ts[2], 0, 12) : '';

        $consistency = "CRITICAL: ALL panels show the EXACT SAME {$desc} — identical design, color, fabric texture, proportions. Same consistent {$modelSubject} throughout, full-body visible in each panel.";

        return match ($templateSet) {
            2 => $isApparel
                ? "[Magazine-style 3-panel collage] {$desc} showcased across 3 lifestyle scenes with thin white card borders. Panel 1: {$s1} — {$modelSubject} {$outfit}, relaxed natural pose, {$s1Label}; Panel 2: {$s2} — model {$outfit}, candid interaction, {$s2Label}; Panel 3: {$s3} — model {$outfit}, full-body visible, {$s3Label}. Heading \"{$msHeading}\" at top. Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. Warm magazine diary aesthetic, natural tones. {$consistency} {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "[Magazine-style 3-panel collage] {$desc} shown across 3 scenes: Panel 1: {$s1} ({$s1Label}); Panel 2: {$s2} ({$s2Label}); Panel 3: {$s3} ({$s3Label}). Heading \"{$msHeading}\" at top. Warm magazine diary tones. CRITICAL: same {$desc} in all panels. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            3 => $isApparel
                ? "[Minimal 2-panel split] Clean bold dividing line at center. LEFT: {$s1}, {$modelSubject} {$outfit}, clean negative space, {$s1Label}. RIGHT: {$s2}, model {$outfit}, airy composition, {$s2Label}. Centered heading \"{$msHeading}\". Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. Luxury minimal aesthetic, high contrast. {$consistency} {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "[Minimal 2-panel split] Clean dividing line. LEFT: {$s1}, {$desc} as hero object. RIGHT: {$s2}, {$desc} in use. Centered heading \"{$msHeading}\". Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. CRITICAL: same {$desc} both sides. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            4 => $isApparel
                ? "[Dynamic diagonal collage] 45-degree bold split. Upper-left: {$s1}, {$modelSubject} {$outfit}, energetic pose, {$s1Label}. Lower-right: {$s2}, model {$outfit}, dynamic movement, {$s2Label}. Centered heading \"{$msHeading}\" bold. Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. High-energy POP composition, saturated vibrant tones. {$consistency} {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "[Dynamic diagonal collage] Bold diagonal split. Upper-left: {$s1}, {$desc} vibrant. Lower-right: {$s2}, {$desc} dynamic. Heading \"{$msHeading}\" bold. {$s1Label} / {$s2Label}. CRITICAL: same {$desc}. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            5 => $isApparel
                ? "[Cinematic 3-panel collage] Deep dark aesthetic, moody lighting. Panel 1: {$s1}, {$modelSubject} {$outfit}, dramatic shadows, {$s1Label}; Panel 2: {$s2}, model {$outfit}, atmospheric, {$s2Label}; Panel 3: {$s3}, model {$outfit}, editorial pose, {$s3Label}. Heading \"{$msHeading}\" in gold. Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. Cinematic dark fashion. {$consistency} {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "[Cinematic 3-panel collage] Dark aesthetic. Panel 1: {$s1} ({$s1Label}). Panel 2: {$s2} ({$s2Label}). Panel 3: {$s3} ({$s3Label}). Heading \"{$msHeading}\" in gold. CRITICAL: same {$desc}. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
            default => $isApparel
                ? "[Commercial Product Showcase Collage] A single {$desc} showcased across 3 distinct lifestyle scenes, emphasizing versatility. All panels show SAME product worn by consistent relatable {$modelSubject}, full-body visible. Scene ①: {$s1} — {$s1Label}, natural light, authentic interaction; Scene ②: {$s2} — {$s2Label}, dynamic natural pose, genuine atmosphere; Scene ③: {$s3} — {$s3Label}, scenic backdrop, full-body shot. Layout: 3 equal vertical panels, thin white dividing lines. Centered heading \"{$msHeading}\" at top with subtle shadow. Bottom-left: \"{$msLeft}\". Bottom-right: \"{$msRight}\". Style: photorealistic commercial photography, soft focus backgrounds, clean composition. Color: warm inviting tones, natural saturation, no oversaturation. {$consistency} {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
                : "[Commercial Product Showcase Collage] A single {$desc} featured across 3 distinct usage scenes. Scene ①: {$s1} — {$s1Label}; Scene ②: {$s2} — {$s2Label}; Scene ③: {$s3} — {$s3Label}. Layout: 3 equal panels, thin white dividers. Centered heading \"{$msHeading}\" at top. Bottom: \"{$msLeft}\" left, \"{$msRight}\" right. Style: photorealistic commercial photography, warm natural tones. CRITICAL: same {$desc} in ALL panels — identical design, color, proportions. {$TEXT_RENDER} {$QUALITY}{$lockTail}{$refTail}",
        };
    }

    private function buildEcommerceDetail(string $desc, array $sp, string $lang, array $ts, array $kfLabels, string $TEXT_RENDER, string $refTail, string $QUALITY): string
    {
        $ecdTitle = $this->spTitle($sp, 0, $lang) ?: ($lang === 'zh' ? '产品详情' : 'PRODUCT DETAILS');
        $ecdSubtitle = $this->spDesc($sp, 0, $lang) ?: ($lang === 'zh' ? '精选品质，值得拥有' : 'Premium Quality, Worth Owning');
        $kfLabel4 = $this->spTitle($sp, 3, $lang) ?: 'Featured';

        $t0 = $ts[0] ?? 'Outdoor';
        $t1 = $ts[1] ?? 'Indoor';
        $t2 = $ts[2] ?? 'Daily';

        return "High-end e-commerce product detail page layout. CRITICAL: {$desc} is the absolute hero product in 80% of frame, right-aligned 45-degree angle, complete structure, sharp edges, no cropping, intact design. \nTOP MODULE (20% height): Main heading \"{$ecdTitle}\" in bold sans-serif, sub-heading \"{$ecdSubtitle}\" below. \nFEATURE ICONS (centered horizontal bar, 4 icons): Icon 1: \"{$kfLabels[0]}\"; Icon 2: \"{$kfLabels[1]}\"; Icon 3: \"{$kfLabels[2]}\"; Icon 4: \"{$kfLabel4}\". \nBOTTOM MODULES (stacked vertically, 30% height): 1. Three-view technical drawing (front/side/back 3D projections with dimension callouts) — based on {$desc} product structure, consistent proportions; 2. Product Parameter Table — Product: {$desc}, Model/Power/Battery/Size/Weight/Interface (filled with realistic specs); 3. Six Core Feature Cards (2×3 grid, each: icon + bold label + short description): Features derived from key selling points - {$kfLabels[0]} / {$kfLabels[1]} / {$kfLabels[2]} / {$kfLabel4}; 4. Usage Scenes (realistic context images): {$t0} / {$t1} / {$t2}; 5. Multi-Color Version Display (2-3 product variations maintaining exact same structure as main product, different colors only). \nVISUAL STYLE: 8K ultra-HD commercial photography, clean professional e-commerce layout, strong product-focused lighting, high contrast, true-to-life colors, sharp text rendering (no distortion), balanced spacing, comprehensive information hierarchy. Background: gradient or clean white, never cluttered. \nCRITICAL CONSTRAINTS: Strict product consistency — same design, same structure, no redesign. All parameters and specifications realistic and accurate. No generic placeholder text. Maintain professional e-commerce standard. {$TEXT_RENDER}{$refTail} {$QUALITY}";
    }

    private function buildThreeAngleView(string $desc, bool $isApparel, string $modelSubject, string $outfit, string $QUALITY, string $lockTail, string $refTail, string $spModelLock): string
    {
        $consistency = "CRITICAL: ALL panels show the EXACT SAME {$desc} — identical design, color, fabric texture, proportions. Same consistent {$modelSubject} throughout, full-body visible in each panel.";

        return $isApparel
            ? "[Three-Angle Product View - Front/Side/Back Collage] A single image divided into THREE EQUAL-SIZED PANELS showing {$modelSubject} wearing {$desc} from different angles. LEFT PANEL: Front view - model facing forward, full body from head to toe visible, showing front design, neckline, and overall silhouette. MIDDLE PANEL: Side view - model standing in profile, full body visible, showing side silhouette, sleeve/sleeveless detail, and fabric drape. RIGHT PANEL: Back view - model with back to camera, full body visible, showing back neckline, back design, and complete back silhouette. CRITICAL REQUIREMENTS: 1. All three panels must show the EXACT SAME {$desc} - identical design, color, print pattern, proportions. 2. All panels must be FULL-BODY shots including head, hair, shoulders, torso, legs, feet. NO cropping or body cutoffs. 3. All three panels must be the SAME SIZE and arranged horizontally with equal width. 4. Use thin white dividers between panels. 5. Clean white/light gray studio background, soft even lighting, professional commercial photography. 6. Sharp focus, natural skin tones, realistic fabric rendering. Layout: [FRONT VIEW | SIDE VIEW | BACK VIEW] - three equal horizontal panels. {$consistency} {$QUALITY}{$lockTail}{$refTail}{$spModelLock}"
            : "[Three-Angle Product View - Front/Side/Back Collage] A single image divided into THREE EQUAL-SIZED PANELS showing {$desc} from different angles. LEFT PANEL: Front view - showing front design, main features, and front details. MIDDLE PANEL: Side view - showing side profile, side details, and dimensional characteristics. RIGHT PANEL: Back view - showing back panel, back design, and reverse-side features. CRITICAL REQUIREMENTS: 1. All three panels must show the EXACT SAME product - identical design, color, proportions. 2. All three panels must be the SAME SIZE and arranged horizontally with equal width. 3. Use thin white dividers between panels. 4. Clean white/light gray studio background, professional commercial photography. Layout: [FRONT VIEW | SIDE VIEW | BACK VIEW] - three equal horizontal panels. {$QUALITY}{$lockTail}{$refTail}";
    }
}
