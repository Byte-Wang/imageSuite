<?php

namespace App\Middleware;

class CorsMiddleware
{
    public function __invoke(string $method, string $uri): ?array
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return null;
    }
}
