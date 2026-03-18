<?php declare(strict_types=1);

namespace Phosphor\Validation\Rule;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email
{
    public function __construct(
        public readonly string $message = '邮箱格式不正确',
    ) {}
}
