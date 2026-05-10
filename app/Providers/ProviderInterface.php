<?php

namespace App\Providers;

interface ProviderInterface
{
    public function generate(string $key, string $prompt, string $baseUrl = '', string $model = '', string $referenceImage = '', array $options = []): string;
}
