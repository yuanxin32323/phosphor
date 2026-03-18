<?php declare(strict_types=1);

namespace Phosphor\Exception;

/**
 * 验证异常 (HTTP 422)
 */
class ValidationException extends HttpException
{
    /** @var array<string, string[]> */
    public readonly array $errors;

    /**
     * @param array<string, string[]> $errors 字段名 → 错误消息列表
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $message = '输入验证失败';
        parent::__construct($message, 422);
    }
}
