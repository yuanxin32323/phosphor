<?php declare(strict_types=1);

namespace Phosphor\Config;

/**
 * 数据库配置
 */
class DatabaseConfig
{
    public function __construct(
        public readonly string $driver = 'sqlite',
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 3306,
        public readonly string $database = 'storage/database.sqlite',
        public readonly string $username = 'root',
        public readonly string $password = '',
        public readonly string $charset = 'utf8mb4',
        /** @var array<int, mixed> PDO options */
        public readonly array $options = [],
    ) {}
}
