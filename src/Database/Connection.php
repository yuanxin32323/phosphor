<?php declare(strict_types=1);

namespace Phosphor\Database;

use Phosphor\Config\DatabaseConfig;
use PDO;

/**
 * 数据库连接管理
 *
 * 封装 PDO，支持 MySQL 和 SQLite。
 */
class Connection
{
    private ?PDO $pdo = null;
    private readonly DatabaseConfig $config;
    private readonly string $basePath;

    public function __construct(DatabaseConfig $config, string $basePath)
    {
        $this->config = $config;
        $this->basePath = $basePath;
    }

    /**
     * 获取 PDO 连接（惰性初始化）
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }
        return $this->pdo;
    }

    /**
     * 执行查询并返回所有行
     *
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行查询并返回第一行
     *
     * @param array<int|string, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public function queryFirst(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * 执行非查询语句（INSERT/UPDATE/DELETE）
     *
     * @param array<int|string, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): int
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /**
     * 获取最后插入的 ID
     */
    public function lastInsertId(): string
    {
        return $this->getPdo()->lastInsertId();
    }

    private function createPdo(): PDO
    {
        $dsn = match ($this->config->driver) {
            'sqlite' => $this->buildSqliteDsn(),
            'mysql' => "mysql:host={$this->config->host};port={$this->config->port};dbname={$this->config->database};charset={$this->config->charset}",
            default => throw new \RuntimeException("不支持的数据库驱动: {$this->config->driver}"),
        };

        $options = $this->config->options + [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($this->config->driver === 'sqlite') {
            return new PDO($dsn, options: $options);
        }

        return new PDO(
            $dsn,
            $this->config->username,
            $this->config->password,
            $options,
        );
    }

    private function buildSqliteDsn(): string
    {
        $dbPath = $this->config->database;
        // 如果是相对路径，基于项目根目录
        if (!str_starts_with($dbPath, '/')) {
            $dbPath = $this->basePath . '/' . $dbPath;
        }
        // 确保目录存在
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return "sqlite:{$dbPath}";
    }
}
