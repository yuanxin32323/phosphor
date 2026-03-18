<?php declare(strict_types=1);

namespace Phosphor\Log;

use Phosphor\Http\Request;
use Throwable;

/**
 * 错误索引写入器
 *
 * 每次发生错误时自动更新 .ai/ERROR_INDEX.md。
 * AI 调试时第一个读此文件，快速定位最近的错误位置。
 */
class ErrorIndexWriter
{
    private string $indexFile;
    private int $maxEntries;

    public function __construct(string $indexFile, int $maxEntries = 50)
    {
        $this->indexFile = $indexFile;
        $this->maxEntries = $maxEntries;
    }

    /**
     * 追加一条错误到索引
     */
    public function append(Throwable $e, ?Request $request = null): void
    {
        $entries = $this->readExisting();

        // 转换为项目相对路径
        $file = $e->getFile();
        $line = $e->getLine();
        $relativePath = $this->toRelativePath($file);

        $entry = [
            'time' => date('H:i:s'),
            'date' => date('Y-m-d'),
            'level' => $this->getLevel($e),
            'file' => $relativePath,
            'line' => $line,
            'message' => mb_substr($e->getMessage(), 0, 80),
            'exception' => (new \ReflectionClass($e))->getShortName(),
            'path' => $request?->getPath() ?? '-',
        ];

        // 添加到开头
        array_unshift($entries, $entry);

        // 限制条目数
        $entries = array_slice($entries, 0, $this->maxEntries);

        $this->writeIndex($entries);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readExisting(): array
    {
        if (!file_exists($this->indexFile)) {
            return [];
        }

        $content = file_get_contents($this->indexFile);
        if ($content === false) {
            return [];
        }

        // 解析现有的 Markdown 表格
        $entries = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '>') || str_starts_with($line, '|--') || str_starts_with($line, '| 时间')) {
                continue;
            }
            if (str_starts_with($line, '|')) {
                $parts = array_map('trim', explode('|', $line));
                $parts = array_filter($parts, fn($p) => $p !== '');
                $parts = array_values($parts);
                if (count($parts) >= 4) {
                    $entries[] = [
                        'time' => $parts[0],
                        'date' => date('Y-m-d'),
                        'level' => $parts[1],
                        'file' => $this->extractFileFromLink($parts[2]),
                        'line' => $this->extractLineFromLink($parts[2]),
                        'message' => $parts[3],
                        'exception' => '',
                        'path' => $parts[4] ?? '-',
                    ];
                }
            }
        }

        return $entries;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function writeIndex(array $entries): void
    {
        $dir = dirname($this->indexFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [];
        $lines[] = '# 最近错误索引（自动生成，勿手动编辑）';
        $lines[] = '';
        $lines[] = '> 最后更新: ' . date('Y-m-d H:i:s');
        $lines[] = '';
        $lines[] = '| 时间 | 级别 | 位置 | 错误摘要 | 请求路径 |';
        $lines[] = '|------|------|------|---------|---------|';

        foreach ($entries as $entry) {
            $fileLink = "[{$entry['file']}:{$entry['line']}](file:///{$entry['file']}#L{$entry['line']})";
            $lines[] = "| {$entry['time']} | {$entry['level']} | {$fileLink} | {$entry['message']} | {$entry['path']} |";
        }

        $lines[] = '';

        file_put_contents($this->indexFile, implode("\n", $lines));
    }

    private function getLevel(Throwable $e): string
    {
        $class = get_class($e);
        if (str_contains($class, 'Validation')) {
            return 'WARN';
        }
        if (str_contains($class, 'NotFound')) {
            return 'WARN';
        }
        return 'ERROR';
    }

    private function toRelativePath(string $absolutePath): string
    {
        $dir = dirname($this->indexFile);
        $basePath = dirname($dir); // .ai/ → 项目根
        if (str_starts_with($absolutePath, $basePath)) {
            return substr($absolutePath, strlen($basePath) + 1);
        }
        return $absolutePath;
    }

    private function extractFileFromLink(string $text): string
    {
        if (preg_match('/\[([^:]+)/', $text, $m)) {
            return $m[1];
        }
        return $text;
    }

    private function extractLineFromLink(string $text): int
    {
        if (preg_match('/:(\d+)/', $text, $m)) {
            return (int) $m[1];
        }
        return 0;
    }
}
