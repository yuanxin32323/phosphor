<?php declare(strict_types=1);

namespace Phosphor\Log;

use Phosphor\Http\Request;

/**
 * 智能日志记录器
 *
 * 分层日志设计，为 AI 调试优化：
 *
 * | 文件               | 内容           | AI 查看优先级 |
 * |--------------------|---------------|--------------|
 * | .ai/ERROR_INDEX.md | 异常摘要       | 🔴 第一 |
 * | storage/logs/debug.log | 业务调试日志 | 🟡 第二 |
 * | storage/logs/app.log   | 全量请求日志 | 🟢 兜底 |
 *
 * debug.log 只保留最近 100 条，小而精，AI 一眼看完。
 * app.log 是全量日志，适合追踪完整请求链路。
 */
class Logger
{
    private string $logFile;
    private string $debugFile;
    private int $debugMaxEntries;
    private ?Request $request = null;

    public function __construct(string $logFile, int $debugMaxEntries = 100)
    {
        $this->logFile = $logFile;
        $this->debugFile = dirname($logFile) . '/debug.log';
        $this->debugMaxEntries = $debugMaxEntries;
    }

    /**
     * 设置当前请求上下文
     */
    public function setRequestContext(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * 业务调试日志 — 写入 debug.log（AI 优先查看）
     *
     * 使用场景：业务代码中需要输出调试信息时调用此方法。
     * debug.log 只保留最近 N 条，AI 调试时先看这里。
     *
     * ```php
     * $this->logger->debug('用户创建请求', ['input' => $input]);
     * $this->logger->debug('查询结果', ['count' => count($users)]);
     * ```
     */
    public function debug(string $message, array $context = []): void
    {
        $entry = $this->buildEntry(LogLevel::DEBUG, $message, $context);
        $json = $this->encodeEntry($entry);

        // 写入 app.log（全量）
        file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);

        // 同时写入 debug.log（限量，AI 优先查看）
        $this->appendToDebugLog($json);
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeToAppLog(LogLevel::INFO, $message, $context);
    }

    public function warn(string $message, array $context = []): void
    {
        $this->writeToAppLog(LogLevel::WARN, $message, $context);
    }

    /**
     * 错误日志 — 写入 app.log + debug.log
     *
     * 错误也写入 debug.log，因为 AI 调试时需要看到错误上下文。
     */
    public function error(string $message, array $context = []): void
    {
        $entry = $this->buildEntry(LogLevel::ERROR, $message, $context);
        $json = $this->encodeEntry($entry);
        file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);
        $this->appendToDebugLog($json);
    }

    public function fatal(string $message, array $context = []): void
    {
        $entry = $this->buildEntry(LogLevel::FATAL, $message, $context);
        $json = $this->encodeEntry($entry);
        file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);
        $this->appendToDebugLog($json);
    }

    /**
     * 通用日志方法（只写 app.log）
     *
     * @param array<string, mixed> $context
     */
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        // debug/error/fatal 有独立方法处理双通道写入
        if ($level === LogLevel::DEBUG) {
            $this->debug($message, $context);
            return;
        }
        if ($level === LogLevel::ERROR) {
            $this->error($message, $context);
            return;
        }
        if ($level === LogLevel::FATAL) {
            $this->fatal($message, $context);
            return;
        }
        $this->writeToAppLog($level, $message, $context);
    }

    /**
     * 获取 debug.log 文件路径（供 AI 导航指引使用）
     */
    public function getDebugLogPath(): string
    {
        return $this->debugFile;
    }

    // ─── 内部方法 ─────────────────────────────────────

    /**
     * 只写入 app.log
     */
    private function writeToAppLog(LogLevel $level, string $message, array $context): void
    {
        $entry = $this->buildEntry($level, $message, $context);
        $json = $this->encodeEntry($entry);
        file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * 追加到 debug.log，保持最新 N 条
     */
    private function appendToDebugLog(string $jsonLine): void
    {
        $lines = [];
        if (file_exists($this->debugFile)) {
            $content = file_get_contents($this->debugFile);
            if ($content !== false && $content !== '') {
                $lines = explode("\n", trim($content));
            }
        }

        $lines[] = $jsonLine;

        // 只保留最新的 N 条
        if (count($lines) > $this->debugMaxEntries) {
            $lines = array_slice($lines, -$this->debugMaxEntries);
        }

        file_put_contents($this->debugFile, implode("\n", $lines) . "\n", LOCK_EX);
    }

    /**
     * 构建日志条目
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildEntry(LogLevel $level, string $message, array $context): array
    {
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
        foreach ($context as $key => $value) {
            $entry[$key] = $this->serializeValue($value);
        }

        return $entry;
    }

    /**
     * 序列化上下文值，确保可 JSON 编码
     */
    private function serializeValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }
        if (is_array($value)) {
            return array_map(fn(mixed $v): mixed => $this->serializeValue($v), $value);
        }
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }
        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }
        if (is_object($value)) {
            // 对象转为类名 + 公开属性
            $data = ['__class' => get_class($value)];
            foreach ((array) $value as $k => $v) {
                // 跳过私有/受保护属性的内部键名
                if (!str_contains($k, "\0")) {
                    $data[$k] = $this->serializeValue($v);
                }
            }
            return $data;
        }
        return (string) $value;
    }

    private function encodeEntry(array $entry): string
    {
        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * 获取调用者信息（自动跳过 Logger 自身的堆栈帧）
     *
     * @return array{layer: string, class: string, function: string, file: string, line: int}|null
     */
    private function getCaller(): ?array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);

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
        $dir = dirname($this->logFile);
        $basePath = dirname($dir, 2);
        if (str_starts_with($absolutePath, $basePath)) {
            return substr($absolutePath, strlen($basePath) + 1);
        }
        return $absolutePath;
    }
}
