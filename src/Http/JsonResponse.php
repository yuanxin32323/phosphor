<?php declare(strict_types=1);

namespace Phosphor\Http;

/**
 * JSON 响应
 *
 * 提供静态工厂方法，让响应构建语义清晰。
 */
class JsonResponse extends Response
{
    /**
     * @param array<string, mixed>|object $data
     */
    public function __construct(array|object $data = [], int $statusCode = 200)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        parent::__construct($json !== false ? $json : '{}', $statusCode);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * 200 成功响应
     *
     * @param array<string, mixed>|object $data
     */
    public static function ok(array|object $data = []): self
    {
        return new self(['code' => 0, 'message' => 'ok', 'data' => $data], 200);
    }

    /**
     * 201 创建成功
     *
     * @param array<string, mixed>|object $data
     */
    public static function created(array|object $data = []): self
    {
        return new self(['code' => 0, 'message' => 'created', 'data' => $data], 201);
    }

    /**
     * 204 无内容
     */
    public static function noContent(): self
    {
        return new self([], 204);
    }

    /**
     * 错误响应
     *
     * @param array<string, mixed> $errors
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): self
    {
        $data = ['code' => $statusCode, 'message' => $message];
        if ($errors !== []) {
            $data['errors'] = $errors;
        }
        return new self($data, $statusCode);
    }
}
