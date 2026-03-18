<?php declare(strict_types=1);

namespace Phosphor\Exception;

/**
 * HTTP 异常基类
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
