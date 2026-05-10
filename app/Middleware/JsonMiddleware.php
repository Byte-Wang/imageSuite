<?php

namespace App\Middleware;

class JsonMiddleware
{
    public function __invoke(string $method, string $uri): ?array
    {
        if ($method === 'POST' || $method === 'PUT') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                $data = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $_POST = array_merge($_POST, $data);
                }
            }
        }

        return null;
    }
}
