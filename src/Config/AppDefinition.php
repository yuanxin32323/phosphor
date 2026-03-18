<?php declare(strict_types=1);

namespace Phosphor\Config;

/**
 * 单个应用定义
 *
 * 描述一个应用的命名空间、URL 前缀和默认中间件。
 */
class AppDefinition
{
    /**
     * @param string $namespace 应用命名空间（如 'Apps\\Api'）
     * @param string $prefix URL 前缀（如 '/api'）
     * @param class-string[] $middleware 应用级中间件列表
     * @param class-string[] $controllers 应用的控制器类列表
     */
    public function __construct(
        public readonly string $namespace,
        public readonly string $prefix,
        public readonly array $middleware = [],
        public readonly array $controllers = [],
    ) {}
}
