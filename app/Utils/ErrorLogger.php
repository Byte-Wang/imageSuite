<?php

namespace App\Utils;

class ErrorLogger
{
    private string $logDir;

    public function __construct(string $logDir)
    {
        $this->logDir = $logDir;
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function writeErrorLog(string $provider, string $typeId, string $prompt, \Throwable $exc): string
    {
        $ts = date('Ymd_His');
        $filename = "{$ts}_{$provider}_{$typeId}.json";
        $path = $this->logDir . '/error_logs';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $log = [
            'timestamp' => date('c'),
            'provider' => $provider,
            'type_id' => $typeId,
            'error_type' => get_class($exc),
            'error' => $exc->getMessage(),
            'prompt_length' => mb_strlen($prompt),
            'prompt' => $prompt,
        ];

        file_put_contents(
            $path . '/' . $filename,
            json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $path . '/' . $filename;
    }

    public function writeLog(string $message, string $level = 'INFO'): void
    {
        $ts = date('Y-m-d H:i:s');
        $line = "[{$ts}] [{$level}] {$message}\n";
        file_put_contents($this->logDir . '/app.log', $line, FILE_APPEND);
    }
}
