<?php

declare(strict_types=1);

namespace App;

// Reads settings from the environment (see .env.example for the full list).
//
// Values can come from two places:
//   - real environment variables (docker-compose, the shell, ...), read with getenv();
//   - the optional .env file, which phpdotenv loads into $_ENV / $_SERVER.
// getenv() is checked first, so a real environment variable always wins over .env.
class Config
{
    public function getString(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        // An empty value ("DB_HOST=") means "use the default".
        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function getInt(string $key, int $default): int
    {
        $value = $this->getString($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function getFloat(string $key, float $default): float
    {
        $value = $this->getString($key);
        return is_numeric($value) ? (float) $value : $default;
    }

    // Accepts true/false, 1/0, yes/no, on/off.
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getString($key);
        return $value === '' ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
