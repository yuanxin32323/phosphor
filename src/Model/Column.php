<?php declare(strict_types=1);

namespace Phosphor\Model;

use Attribute;

/**
 * 数据库字段 Attribute
 *
 * 在 Model 属性上声明数据库列的元信息。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $type = 'varchar',
        public readonly int $length = 255,
        public readonly bool $primary = false,
        public readonly bool $autoIncrement = false,
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
        public readonly bool $index = false,
        public readonly mixed $default = null,
        public readonly ?string $enumClass = null,
        public readonly bool $autoCreate = false,
        public readonly bool $autoUpdate = false,
        public readonly bool $hidden = false,
    ) {}
}
