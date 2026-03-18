<?php declare(strict_types=1);

namespace Phosphor;

use Phosphor\Config\AppConfig;
use Phosphor\Config\AppDefinition;
use Phosphor\Config\ConfigLoader;
use Phosphor\Config\DatabaseConfig;
use Phosphor\Container\Container;
use Phosphor\Database\Connection;
use Phosphor\Exception\ExceptionHandler;
use Phosphor\Http\JsonResponse;
use Phosphor\Http\Request;
use Phosphor\Http\Response;
use Phosphor\Log\Logger;
use Phosphor\Log\ErrorIndexWriter;
use Phosphor\Middleware\MiddlewareInterface;
use Phosphor\Middleware\MiddlewarePipeline;
use Phosphor\Routing\Router;
use Phosphor\Routing\RouteMatch;
use Phosphor\Validation\Validator;
use Phosphor\DTO\InputDTO;
use Phosphor\Event\EventDispatcher;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * 应用内核
 *
 * 负责完整的请求生命周期：
 * 加载配置 → 初始化容器 → 注册路由 → 匹配请求 → 执行中间件 → 调用控制器 → 返回响应
 */
class Kernel
{
    private Container $container;
    private Router $router;
    private AppConfig $appConfig;
    private ConfigLoader $configLoader;
    private Logger $logger;

    public function __construct(string $basePath)
    {
        $this->container = new Container();
        $this->router = new Router();
        $this->configLoader = new ConfigLoader($basePath);

        // 加载环境变量和配置
        $this->configLoader->loadEnv();
        $this->appConfig = $this->configLoader->loadAppConfig();

        // 初始化日志系统
        $logDir = $basePath . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logger = new Logger($logDir . '/app.log');

        // 注册核心单例
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(AppConfig::class, $this->appConfig);
        $this->container->instance(Logger::class, $this->logger);
        $this->container->instance(ConfigLoader::class, $this->configLoader);
        $this->container->instance(Validator::class, new Validator());
        $this->container->instance(EventDispatcher::class, new EventDispatcher());

        // 初始化数据库连接
        $dbConfig = $this->configLoader->loadDatabaseConfig();
        $this->container->instance(DatabaseConfig::class, $dbConfig);
        $this->container->singleton(Connection::class, function () use ($dbConfig, $basePath): Connection {
            return new Connection($dbConfig, $basePath);
        });

        // 初始化错误索引写入器
        $aiDir = $basePath . '/.ai';
        $this->container->instance(
            ErrorIndexWriter::class,
            new ErrorIndexWriter($aiDir . '/ERROR_INDEX.md')
        );

        // 注册所有应用的路由
        $this->registerApps();
    }

    /**
     * 处理 HTTP 请求
     */
    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);
        $this->logger->setRequestContext($request);

        try {
            // 匹配路由
            $routeMatch = $this->router->match($request);

            if ($routeMatch === null) {
                $this->logger->warn('Route not found', [
                    'path' => $request->getPath(),
                    'method' => $request->getMethod()->value,
                ]);
                return JsonResponse::error('Not Found', 404);
            }

            // 设置路由参数
            $request->setParams($routeMatch->params);

            // 构建中间件管道
            $pipeline = $this->buildMiddlewarePipeline($routeMatch);

            // 执行管道（控制器调用是最内层）
            $response = $pipeline->run($request, function (Request $req) use ($routeMatch): Response {
                return $this->callController($routeMatch, $req);
            });

            // 记录请求日志
            $this->logger->info('Request completed', [
                'status' => $response->getStatusCode(),
            ]);

            return $response;

        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * 获取容器（供外部注册绑定）
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * 获取路由器（供项目地图生成器使用）
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * 注册所有应用的控制器到路由
     */
    private function registerApps(): void
    {
        foreach ($this->appConfig->apps as $appName => $appDef) {
            foreach ($appDef->controllers as $controllerClass) {
                $this->router->registerController($controllerClass, $appDef->prefix);
            }
        }
    }

    /**
     * 构建中间件管道
     */
    private function buildMiddlewarePipeline(RouteMatch $routeMatch): MiddlewarePipeline
    {
        $pipeline = new MiddlewarePipeline();

        // 添加应用级中间件
        $appDef = $this->findAppForRoute($routeMatch);
        if ($appDef !== null) {
            foreach ($appDef->middleware as $middlewareClass) {
                $middleware = $this->container->make($middlewareClass);
                if ($middleware instanceof MiddlewareInterface) {
                    $pipeline->pipe($middleware);
                }
            }
        }

        // 添加路由级中间件
        foreach ($routeMatch->middlewares as $middlewareAttr) {
            $middleware = $this->container->make($middlewareAttr->class);
            if ($middleware instanceof MiddlewareInterface) {
                $pipeline->pipe($middleware);
            }
        }

        return $pipeline;
    }

    /**
     * 调用控制器方法
     */
    private function callController(RouteMatch $routeMatch, Request $request): Response
    {
        $controller = $this->container->make($routeMatch->controllerClass);
        $method = new ReflectionMethod($controller, $routeMatch->method);

        // 解析方法参数
        $args = $this->resolveMethodArgs($method, $request);

        $result = $method->invokeArgs($controller, $args);

        if ($result instanceof Response) {
            return $result;
        }

        // 如果返回数组或对象，自动包装为 JsonResponse
        if (is_array($result) || is_object($result)) {
            return JsonResponse::ok($result);
        }

        return new Response((string) $result);
    }

    /**
     * 解析控制器方法的参数
     *
     * @return array<int, mixed>
     */
    private function resolveMethodArgs(ReflectionMethod $method, Request $request): array
    {
        $args = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = ($type instanceof ReflectionNamedType) ? $type->getName() : null;

            // 1. 如果参数类型是 Request，注入请求对象
            if ($typeName === Request::class) {
                $args[] = $request;
                continue;
            }

            // 2. 如果参数类型是 InputDTO 子类，从请求体自动填充并验证
            if ($typeName !== null && class_exists($typeName) && is_subclass_of($typeName, InputDTO::class)) {
                /** @var InputDTO $dto */
                $dto = $typeName::fromArray($request->all());
                $validator = $this->container->make(Validator::class);
                $validator->validate($dto);
                $args[] = $dto;
                continue;
            }

            // 3. 如果参数名匹配路由参数，注入路由参数
            $paramName = $param->getName();
            $routeParams = $request->params();
            if (isset($routeParams[$paramName])) {
                $value = $routeParams[$paramName];
                // 类型转换
                if ($typeName === 'int') {
                    $args[] = (int) $value;
                } elseif ($typeName === 'float') {
                    $args[] = (float) $value;
                } else {
                    $args[] = $value;
                }
                continue;
            }

            // 4. 尝试从容器解析
            if ($typeName !== null && !$type->isBuiltin()) {
                $args[] = $this->container->make($typeName);
                continue;
            }

            // 5. 使用默认值
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            $args[] = null;
        }

        return $args;
    }

    /**
     * 查找路由所属的应用定义
     */
    private function findAppForRoute(RouteMatch $routeMatch): ?AppDefinition
    {
        foreach ($this->appConfig->apps as $appDef) {
            foreach ($appDef->controllers as $controllerClass) {
                if ($controllerClass === $routeMatch->controllerClass) {
                    return $appDef;
                }
            }
        }
        return null;
    }

    /**
     * 统一异常处理
     */
    private function handleException(Throwable $e, Request $request): Response
    {
        // 记录错误日志
        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->filterTrace($e),
        ]);

        // 更新错误索引
        try {
            /** @var ErrorIndexWriter $indexWriter */
            $indexWriter = $this->container->make(ErrorIndexWriter::class);
            $indexWriter->append($e, $request);
        } catch (Throwable) {
            // 错误索引写入失败不影响主流程
        }

        // 使用异常处理器转换为响应
        $handler = new ExceptionHandler($this->appConfig->debug);
        return $handler->handle($e);
    }

    /**
     * 过滤堆栈追踪，只保留项目文件
     *
     * @return string[]
     */
    private function filterTrace(Throwable $e): array
    {
        $basePath = $this->configLoader->getBasePath();
        $trace = [];
        foreach ($e->getTrace() as $frame) {
            if (isset($frame['file'])) {
                $file = $frame['file'];
                if (str_starts_with($file, $basePath)) {
                    $relative = substr($file, strlen($basePath) + 1);
                    $line = $frame['line'] ?? 0;
                    $trace[] = "{$relative}:{$line}";
                }
            }
        }
        return array_slice($trace, 0, 10); // 最多保留 10 层
    }
}
