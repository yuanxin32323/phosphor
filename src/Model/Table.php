<?php declare(strict_types=1);

namespace Phosphor\Model;

use Attribute;

/**
 * 数据库表名 Attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public readonly string $name,
    ) {}
}
