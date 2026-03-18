<?php declare(strict_types=1);

namespace Phosphor\Routing;

use Phosphor\Http\Method;
use Phosphor\Http\Request;
use ReflectionClass;
use ReflectionMethod;

/**
 * 路由器
 *
 * 通过反射扫描 Controller 类上的 #[Route] Attribute，构建路由表并匹配请求。
 */
class Router
{
    /**
     * 已注册的路由定义
     *
     * @var array<string, array{
     *   pattern: string,
     *   regex: string,
     *   paramNames: string[],
     *   controllerClass: class-string,
     *   method: string,
     *   middlewares: Middleware[]
     * }>
     */
    private array $routes = [];

    /**
     * 注册一个 Controller 类的所有路由
     *
     * @param class-string $controllerClass
     * @param string $prefix URL 前缀（来自应用定义）
     */
    public function registerController(string $controllerClass, string $prefix = ''): void
    {
        $reflection = new ReflectionClass($controllerClass);

        // 收集类级中间件
        $classMiddlewares = $this->extractMiddlewares($reflection);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $routeAttributes = $method->getAttributes(Route::class);

            foreach ($routeAttributes as $routeAttr) {
                /** @var Route $route */
                $route = $routeAttr->newInstance();

                // 合并类级 + 方法级中间件
                $methodMiddlewares = $this->extractMiddlewaresFromMethod($method);
                $allMiddlewares = array_merge($classMiddlewares, $methodMiddlewares);

                $fullPath = rtrim($prefix, '/') . '/' . ltrim($route->path, '/');
                $fullPath = '/' . ltrim($fullPath, '/');

                // 解析路径参数（{id} → 正则分组）
                $paramNames = [];
                $regex = preg_replace_callback('/\{(\w+)\}/', function (array $matches) use (&$paramNames): string {
                    $paramNames[] = $matches[1];
                    return '([^/]+)';
                }, $fullPath);

                $key = $route->method->value . ':' . $fullPath;
                $this->routes[$key] = [
                    'pattern' => $fullPath,
                    'regex' => '#^' . $regex . '$#',
                    'paramNames' => $paramNames,
                    'controllerClass' => $controllerClass,
                    'method' => $method->getName(),
                    'httpMethod' => $route->method,
                    'middlewares' => $allMiddlewares,
                ];
            }
        }
    }

    /**
     * 匹配请求到路由
     */
    public function match(Request $request): ?RouteMatch
    {
        $requestMethod = $request->getMethod();
        $requestPath = rtrim($request->getPath(), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['httpMethod'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['regex'], $requestPath, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $index => $name) {
                    $params[$name] = $matches[$index + 1];
                }

                return new RouteMatch(
                    controllerClass: $route['controllerClass'],
                    method: $route['method'],
                    params: $params,
                    middlewares: $route['middlewares'],
                );
            }
        }

        return null;
    }

    /**
     * 获取所有已注册路由（供 PROJECT_MAP 生成器使用）
     *
     * @return array<int, array{method: string, path: string, controller: string, action: string, middlewares: string[]}>
     */
    public function getRegisteredRoutes(): array
    {
        $result = [];
        foreach ($this->routes as $route) {
            $middlewareNames = array_map(
                fn(Middleware $m): string => (new ReflectionClass($m->class))->getShortName(),
                $route['middlewares']
            );
            $result[] = [
                'method' => $route['httpMethod']->value,
                'path' => $route['pattern'],
                'controller' => (new ReflectionClass($route['controllerClass']))->getShortName() . '::' . $route['method'],
                'action' => $route['method'],
                'middlewares' => $middlewareNames,
            ];
        }
        return $result;
    }

    /**
     * 从类反射提取 Middleware Attribute
     *
     * @return Middleware[]
     */
    private function extractMiddlewares(ReflectionClass $reflection): array
    {
        $middlewares = [];
        foreach ($reflection->getAttributes(Middleware::class) as $attr) {
            $middlewares[] = $attr->newInstance();
        }
        return $middlewares;
    }

    /**
     * 从方法反射提取 Middleware Attribute
     *
     * @return Middleware[]
     */
    private function extractMiddlewaresFromMethod(ReflectionMethod $method): array
    {
        $middlewares = [];
        foreach ($method->getAttributes(Middleware::class) as $attr) {
            $middlewares[] = $attr->newInstance();
        }
        return $middlewares;
    }
}
