<?php declare(strict_types=1);

namespace Phosphor\Config;

/**
 * 配置加载器
 *
 * 加载 .env 文件和 config/ 目录下的配置。
 */
class ConfigLoader
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * 加载 .env 文件到环境变量
     */
    public function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // 跳过注释
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // 移除引号
                $value = trim($value, '"\'');
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * 加载应用配置
     */
    public function loadAppConfig(): AppConfig
    {
        $configFile = $this->basePath . '/config/app.php';
        if (!file_exists($configFile)) {
            return new AppConfig();
        }
        $config = require $configFile;
        if ($config instanceof AppConfig) {
            return $config;
        }
        return new AppConfig();
    }

    /**
     * 加载数据库配置
     */
    public function loadDatabaseConfig(): DatabaseConfig
    {
        $configFile = $this->basePath . '/config/database.php';
        if (!file_exists($configFile)) {
            return new DatabaseConfig();
        }
        $config = require $configFile;
        if ($config instanceof DatabaseConfig) {
            return $config;
        }
        return new DatabaseConfig();
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
