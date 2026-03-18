<?php declare(strict_types=1);

namespace Phosphor\Validation\Rule;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(
        public readonly int $max,
        public readonly string $message = '',
    ) {}

    public function getMessage(): string
    {
        return $this->message ?: "长度不能超过 {$this->max} 个字符";
    }
}
