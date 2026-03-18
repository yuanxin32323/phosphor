<?php declare(strict_types=1);

namespace Phosphor\Log;

use Phosphor\Http\Request;

/**
 * 智能日志记录器
 *
 * 核心设计：每条日志是一行 JSON，包含精准的文件定位信息，
 * 让 AI 能直接从日志跳转到出错的代码位置，无需遍历项目。
 *
 * 日志格式：JSON Lines（每行一个 JSON 对象）
 */
class Logger
{
    private string $logFile;
    private ?Request $request = null;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * 设置当前请求上下文
     */
    public function setRequestContext(Request $request): void
    {
        $this->request = $request;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function warn(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARN, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function fatal(string $message, array $context = []): void
    {
        $this->log(LogLevel::FATAL, $message, $context);
    }

    /**
     * 写入一条结构化日志
     *
     * @param array<string, mixed> $context
     */
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        // 自动获取调用位置
        $caller = $this->getCaller();

        $entry = [
            'time' => date('c'),
            'level' => $level->value,
            'message' => $message,
        ];

        // 添加请求上下文
        if ($this->request !== null) {
            $reqCtx = $this->request->toLogContext();
            $entry['request_id'] = $reqCtx['request_id'];
            $entry['method'] = $reqCtx['method'];
            $entry['path'] = $reqCtx['path'];
        }

        // 添加调用位置
        if ($caller !== null) {
            $entry['layer'] = $caller['layer'];
            $entry['class'] = $caller['class'];
            $entry['function'] = $caller['function'];
            $entry['file'] = $caller['file'];
            $entry['line'] = $caller['line'];
        }

        // 合并额外上下文
        if ($context !== []) {
            foreach ($context as $key => $value) {
                $entry[$key] = $value;
            }
        }

        // 写入 JSON Lines
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取调用者信息（自动跳过 Logger 自身的堆栈帧）
     *
     * @return array{layer: string, class: string, function: string, file: string, line: int}|null
     */
    private function getCaller(): ?array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // 跳过 Logger 自身的帧
        foreach ($trace as $frame) {
            $class = $frame['class'] ?? '';
            if ($class === self::class) {
                continue;
            }
            if ($class === '') {
                continue;
            }

            return [
                'layer' => $this->detectLayer($class),
                'class' => $class,
                'function' => $frame['function'] ?? '',
                'file' => $this->toRelativePath($frame['file'] ?? ''),
                'line' => $frame['line'] ?? 0,
            ];
        }

        return null;
    }

    /**
     * 根据类名自动检测所属层级
     */
    private function detectLayer(string $class): string
    {
        if (str_contains($class, '\\Controllers\\')) {
            return 'Controller';
        }
        if (str_contains($class, '\\Services\\')) {
            return 'Service';
        }
        if (str_contains($class, '\\Repositories\\')) {
            return 'Repository';
        }
        if (str_contains($class, '\\Middleware\\')) {
            return 'Middleware';
        }
        if (str_contains($class, '\\Listeners\\')) {
            return 'Listener';
        }
        if (str_contains($class, 'Kernel')) {
            return 'Kernel';
        }
        return 'Framework';
    }

    /**
     * 转换为项目相对路径
     */
    private function toRelativePath(string $absolutePath): string
    {
        // 尝试找到项目根目录（包含 composer.json 的目录）
        $dir = dirname($this->logFile);
        // storage/logs/ → 项目根目录上两级
        $basePath = dirname($dir, 2);
        if (str_starts_with($absolutePath, $basePath)) {
            return substr($absolutePath, strlen($basePath) + 1);
        }
        return $absolutePath;
    }
}
