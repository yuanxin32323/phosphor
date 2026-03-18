<?php declare(strict_types=1);

namespace Phosphor\Routing;

use Phosphor\Http\Method;

/**
 * 路由匹配结果
 *
 * 当 Router 匹配到请求时，返回此对象，包含控制器类、方法名、路径参数和中间件列表。
 */
class RouteMatch
{
    /**
     * @param class-string $controllerClass 控制器完整类名
     * @param string $method 控制器方法名
     * @param array<string, string> $params 路径参数（如 {id} => '123'）
     * @param Middleware[] $middlewares 合并后的中间件列表（类级 + 方法级）
     */
    public function __construct(
        public readonly string $controllerClass,
        public readonly string $method,
        public readonly array $params = [],
        public readonly array $middlewares = [],
    ) {}
}
