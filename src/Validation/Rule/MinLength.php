<?php declare(strict_types=1);

namespace Phosphor\Validation\Rule;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength
{
    public function __construct(
        public readonly int $min,
        public readonly string $message = '',
    ) {}

    public function getMessage(): string
    {
        return $this->message ?: "长度不能少于 {$this->min} 个字符";
    }
}
