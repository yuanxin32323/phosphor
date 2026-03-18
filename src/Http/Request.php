<?php declare(strict_types=1);

namespace Phosphor\Http;

/**
 * HTTP 请求封装
 *
 * 将 PHP 超全局变量封装为类型安全的对象，所有数据通过显式方法访问。
 */
class Request
{
    /** @var array<string, string> */
    private readonly array $query;

    /** @var array<string, mixed> */
    private readonly array $body;

    /** @var array<string, string> */
    private readonly array $headers;

    /** @var array<string, mixed> */
    private readonly array $server;

    /** @var array<string, mixed> */
    private readonly array $files;

    /** @var array<string, string> 路由路径参数 */
    private array $params = [];

    private readonly string $requestId;

    public function __construct(
        array $query = [],
        array $body = [],
        array $headers = [],
        array $server = [],
        array $files = [],
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->server = $server;
        $this->files = $files;
        $this->requestId = 'req_' . bin2hex(random_bytes(8));
    }

    /**
     * 从 PHP 超全局变量创建 Request 实例
     */
    public static function capture(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$headerName] = (string) $value;
            }
        }

        $body = [];
        $contentType = $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody !== false && $rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            }
        } else {
            $body = $_POST;
        }

        return new self(
            query: $_GET,
            body: $body,
            headers: $headers,
            server: $_SERVER,
            files: $_FILES,
        );
    }

    public function getMethod(): Method
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        return Method::from($method);
    }

    public function getPath(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        return $path !== false && $path !== null ? $path : '/';
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * 获取查询参数
     */
    public function query(string $key, string $default = ''): string
    {
        return (string) ($this->query[$key] ?? $default);
    }

    /**
     * 获取请求体字段
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * 获取完整请求体
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * 获取请求头
     */
    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * 设置路由路径参数
     *
     * @param array<string, string> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * 获取路由路径参数
     */
    public function param(string $key, string $default = ''): string
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * 获取所有路由路径参数
     *
     * @return array<string, string>
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * 转换为日志上下文数组（供 Logger 使用）
     *
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'request_id' => $this->requestId,
            'method' => $this->getMethod()->value,
            'path' => $this->getPath(),
            'input' => $this->body,
        ];
    }
}
