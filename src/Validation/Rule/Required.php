<?php declare(strict_types=1);

namespace Phosphor\Validation\Rule;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Required
{
    public function __construct(
        public readonly string $message = '此字段为必填项',
    ) {}
}
