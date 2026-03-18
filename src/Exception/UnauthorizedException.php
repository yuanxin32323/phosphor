<?php declare(strict_types=1);

namespace Phosphor\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = '未授权访问')
    {
        parent::__construct($message, 401);
    }
}
