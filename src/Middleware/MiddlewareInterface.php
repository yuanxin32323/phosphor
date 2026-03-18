<?php declare(strict_types=1);

namespace Phosphor\Middleware;

use Closure;
use Phosphor\Http\Request;
use Phosphor\Http\Response;

/**
 * 中间件接口
 *
 * 所有中间件必须实现此接口。
 * handle 方法接收请求和下一个处理器，返回响应。
 */
interface MiddlewareInterface
{
    /**
     * 处理请求
     *
     * @param Request $request 当前请求
     * @param Closure(Request): Response $next 下一个处理器
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
