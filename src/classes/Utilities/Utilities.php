<?php

namespace Utilities;

use Dotenv\Dotenv;

class Utilities
{
    /**
     * @param string $name
     * @param null $default
     * @return string
     * @throws \Exception
     */
    public static function env(string $name, $default = NULL): string
    {
        $value = getenv($name);
        if (!empty($value)) {
            return $value;
        } elseif (!is_null($default)) {
            return $default;
        } else {
            throw new \Exception('Environment variable ' . $name . ' not found or has no value');
        }
    }

    /**
     * @param array $array
     * @param string $key
     * @return bool
     */
    public static function hasValue(array $array, string $key): bool
    {
        return is_array($array) && array_key_exists($key, $array) && !empty($array[$key]);
    }

    /**
     * Load environment variables
     */
    public static function loadEnvVariables()
    {
        $envFile = ROOT . '.env';
        if (file_exists($envFile)) {
            $dotenv = Dotenv::create(ROOT);
            $dotenv->load();
        }
    }
}