<?php declare(strict_types=1);

namespace Phosphor\Config;

/**
 * 全局应用配置
 */
class AppConfig
{
    /**
     * @param string $name 应用名称
     * @param string $env 运行环境 (local/production)
     * @param bool $debug 是否开启调试模式
     * @param array<string, AppDefinition> $apps 已注册的应用列表
     */
    public function __construct(
        public readonly string $name = 'Phosphor',
        public readonly string $env = 'local',
        public readonly bool $debug = true,
        public readonly array $apps = [],
    ) {}
}
