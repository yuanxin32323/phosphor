<?php declare(strict_types=1);

namespace Phosphor\Routing;

use Attribute;

/**
 * 中间件 Attribute
 *
 * 可标记在 Controller 类（应用于所有方法）或单个方法上。
 * 支持传递中间件参数。
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param class-string $class 中间件类名
     * @param array<string, mixed> $params 中间件参数
     */
    public function __construct(
        public readonly string $class,
        public readonly array $params = [],
    ) {}
}
