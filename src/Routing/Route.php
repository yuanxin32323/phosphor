<?php declare(strict_types=1);

namespace Phosphor\Routing;

use Attribute;
use Phosphor\Http\Method;

/**
 * 路由 Attribute
 *
 * 标记在 Controller 方法上，声明 HTTP 方法和路径。
 * 路径相对于应用前缀，例如 '/users' 在 API 应用中实际为 '/api/users'。
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public readonly Method $method,
        public readonly string $path,
    ) {}
}
