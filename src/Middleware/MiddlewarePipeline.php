<?php declare(strict_types=1);

namespace Phosphor\Middleware;

use Closure;
use Phosphor\Http\Request;
use Phosphor\Http\Response;

/**
 * 中间件管道
 *
 * 按顺序执行中间件链，最终调用核心处理器。
 */
class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    /**
     * 添加中间件到管道
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * 执行管道
     *
     * @param Request $request 请求对象
     * @param Closure(Request): Response $core 最终处理器（通常是控制器调用）
     * @return Response
     */
    public function run(Request $request, Closure $core): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function (Closure $next, MiddlewareInterface $middleware): Closure {
                return function (Request $request) use ($middleware, $next): Response {
                    return $middleware->handle($request, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }
}
