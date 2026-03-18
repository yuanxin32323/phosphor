<?php declare(strict_types=1);

namespace Phosphor\Container;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * 依赖注入容器
 *
 * 通过反射解析构造函数参数类型，自动创建依赖对象。
 * 支持 bind（每次新建）和 singleton（单例）两种绑定模式。
 */
class Container
{
    /** @var array<string, callable> 绑定的工厂函数 */
    private array $bindings = [];

    /** @var array<string, object> 单例实例缓存 */
    private array $singletons = [];

    /** @var array<string, true> 标记为单例的类名 */
    private array $singletonKeys = [];

    /**
     * 绑定一个类或接口到工厂函数
     *
     * @param string $abstract 类名或接口名
     * @param callable(): object $factory 工厂函数
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * 绑定一个单例
     *
     * @param string $abstract 类名或接口名
     * @param callable(): object|null $factory 工厂函数（null 则通过反射自动解析）
     */
    public function singleton(string $abstract, ?callable $factory = null): void
    {
        $this->singletonKeys[$abstract] = true;
        if ($factory !== null) {
            $this->bindings[$abstract] = $factory;
        }
    }

    /**
     * 注册一个已有实例为单例
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->singletons[$abstract] = $instance;
        $this->singletonKeys[$abstract] = true;
    }

    /**
     * 解析并创建一个类的实例
     *
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): object
    {
        // 检查单例缓存
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        // 使用绑定的工厂函数
        if (isset($this->bindings[$abstract])) {
            $instance = ($this->bindings[$abstract])($this);
        } else {
            // 通过反射自动解析
            $instance = $this->resolve($abstract);
        }

        // 如果标记为单例，缓存实例
        if (isset($this->singletonKeys[$abstract])) {
            $this->singletons[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * 检查是否已绑定
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->singletons[$abstract]);
    }

    /**
     * 通过反射解析构造函数依赖并创建实例
     */
    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Container: 无法解析类 '{$class}'，类不存在。");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Container: 类 '{$class}' 不可实例化（可能是接口或抽象类），请先调用 bind() 注册绑定。");
        }

        $constructor = $reflection->getConstructor();

        // 无构造函数，直接实例化
        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                // 依赖是对象类型，递归解析
                $dependencies[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Container: 无法解析 '{$class}' 构造函数参数 '\${$param->getName()}'。"
                    . " 类型为标量或未声明类型，请通过 bind() 显式注册。"
                );
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
