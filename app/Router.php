<?php

namespace App;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function addMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function get(string $path, callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = compact('method', 'path', 'handler');
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        // 处理 public 目录前缀（当从 public 目录访问时）
        if (strpos($uri, '/public') === 0) {
            $uri = substr($uri, 7); // 移除 /public
        }

        // 处理 SCRIPT_NAME 前缀（Apache 环境下）
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // 标准化 URI
        $uri = '/' . trim($uri, '/');

        // 执行中间件
        foreach ($this->middlewares as $mw) {
            $response = $mw($method, $uri);
            if ($response !== null) {
                $this->sendJson($response);
                return;
            }
        }

        // 匹配路由
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compilePattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $result = call_user_func($route['handler'], $params);
                $this->sendJson($result);
                return;
            }
        }

        $this->sendJson(['error' => 'Not Found', 'code' => 404], 404);
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function sendJson($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
