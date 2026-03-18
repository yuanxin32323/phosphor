<?php declare(strict_types=1);

namespace Phosphor\Exception;

use Phosphor\Http\JsonResponse;
use Phosphor\Http\Response;
use Throwable;

/**
 * 异常处理器
 *
 * 将异常统一转换为 JSON 响应。调试模式下包含详细错误信息。
 */
class ExceptionHandler
{
    public function __construct(
        private readonly bool $debug = false,
    ) {}

    public function handle(Throwable $e): Response
    {
        if ($e instanceof ValidationException) {
            return JsonResponse::error($e->getMessage(), 422, $e->errors);
        }

        if ($e instanceof HttpException) {
            $data = ['code' => $e->statusCode, 'message' => $e->getMessage()];
            if ($this->debug) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
            return new JsonResponse($data, $e->statusCode);
        }

        // 未知异常
        $statusCode = 500;
        $data = ['code' => $statusCode, 'message' => '服务器内部错误'];

        if ($this->debug) {
            $data['message'] = $e->getMessage();
            $data['exception'] = get_class($e);
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = array_slice(
                array_map(fn($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? 0), $e->getTrace()),
                0,
                10
            );
        }

        return new JsonResponse($data, $statusCode);
    }
}
