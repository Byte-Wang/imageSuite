<?php

namespace App\Controllers;

use App\Services\ProviderService;

class ProviderController
{
    private ProviderService $service;

    public function __construct(ProviderService $service)
    {
        $this->service = $service;
    }

    public function index(array $params): array
    {
        return ['success' => true, 'data' => $this->service->checkProviders()];
    }
}
