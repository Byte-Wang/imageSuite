<?php

namespace App\Utils;

class ConfigHelper
{
    public static function getApiKey(array $providerConfig): string
    {
        $key = $providerConfig['api_key'] ?? '';
        if (strlen($key) > 5) {
            return trim($key);
        }

        $envKey = $providerConfig['env_key'] ?? '';
        if ($envKey) {
            $key = getenv($envKey) ?: '';
            if (strlen($key) > 5) {
                return trim($key);
            }
        }

        $envKeyAlt = $providerConfig['env_key_alt'] ?? '';
        if ($envKeyAlt) {
            $key = getenv($envKeyAlt) ?: '';
            if (strlen($key) > 5) {
                return trim($key);
            }
        }

        return '';
    }

    public static function getBaseUrl(array $providerConfig): string
    {
        $url = $providerConfig['base_url'] ?? '';
        if (strlen($url) > 5) {
            return trim($url);
        }

        $envUrl = $providerConfig['env_url'] ?? '';
        if ($envUrl) {
            $url = getenv($envUrl) ?: '';
            if (strlen($url) > 5) {
                return trim($url);
            }
        }

        return $providerConfig['default_url'] ?? '';
    }

    public static function getModel(array $providerConfig, string $cliModel = ''): string
    {
        if (strlen($cliModel) > 2) {
            return trim($cliModel);
        }

        $model = $providerConfig['model'] ?? '';
        if (strlen($model) > 2) {
            return trim($model);
        }

        $envModel = $providerConfig['env_model'] ?? '';
        if ($envModel) {
            $model = getenv($envModel) ?: '';
            if (strlen($model) > 2) {
                return trim($model);
            }
        }

        return $providerConfig['default_model'] ?? '';
    }
}
