<?php declare(strict_types=1);

namespace Phosphor\Navigation;

/**
 * 变更日志记录器
 *
 * 每次项目发生变更时追加记录到 .ai/CHANGELOG.md。
 * AI 打开项目后读此文件，了解最近发生了什么变化，避免重新扫描整个项目。
 *
 * 设计要点：
 * - 按日期分组
 * - 支持多种变更类型：NEW / MODIFY / DELETE / FIX
 * - 最新的记录在最前面
 * - 限制最大条目数避免文件无限增长
 */
class ChangelogWriter
{
    private string $outputFile;
    private int $maxEntries;

    public function __construct(string $outputFile, int $maxEntries = 200)
    {
        $this->outputFile = $outputFile;
        $this->maxEntries = $maxEntries;
    }

    /**
     * 追加一条变更记录
     *
     * @param string $type 变更类型：NEW, MODIFY, DELETE, FIX
     * @param string $description 变更描述
     * @param string[] $files 涉及的文件列表（相对路径）
     */
    public function append(string $type, string $description, array $files = []): void
    {
        $entries = $this->readExisting();

        $today = date('Y-m-d');
        $time = date('H:i:s');

        $fileList = '';
        if ($files !== []) {
            $fileLinks = array_map(fn(string $f): string => "`{$f}`", $files);
            $fileList = ' (' . implode(', ', $fileLinks) . ')';
        }

        $entry = [
            'date' => $today,
            'time' => $time,
            'type' => strtoupper($type),
            'description' => $description,
            'files' => $fileList,
        ];

        // 插入到开头
        array_unshift($entries, $entry);

        // 限制条目数
        $entries = array_slice($entries, 0, $this->maxEntries);

        $this->writeFile($entries);
    }

    /**
     * 批量追加多条变更
     *
     * @param array<int, array{type: string, description: string, files?: string[]}> $changes
     */
    public function appendBatch(array $changes): void
    {
        foreach ($changes as $change) {
            $this->append(
                $change['type'],
                $change['description'],
                $change['files'] ?? [],
            );
        }
    }

    /**
     * @return array<int, array{date: string, time: string, type: string, description: string, files: string}>
     */
    private function readExisting(): array
    {
        if (!file_exists($this->outputFile)) {
            return [];
        }

        $content = file_get_contents($this->outputFile);
        if ($content === false) {
            return [];
        }

        $entries = [];
        $currentDate = '';
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // 匹配日期标题 "## 2026-03-19"
            if (preg_match('/^## (\d{4}-\d{2}-\d{2})$/', $line, $m)) {
                $currentDate = $m[1];
                continue;
            }

            // 匹配条目 "- `14:30:00` [NEW] 描述 (文件)"
            if (preg_match('/^- `(\d{2}:\d{2}:\d{2})` \[(\w+)\] (.+)$/', $line, $m)) {
                $desc = $m[3];
                $files = '';
                // 提取末尾的文件列表
                if (preg_match('/^(.+?) \((.+)\)$/', $desc, $fm)) {
                    $desc = $fm[1];
                    $files = ' (' . $fm[2] . ')';
                }
                $entries[] = [
                    'date' => $currentDate,
                    'time' => $m[1],
                    'type' => $m[2],
                    'description' => $desc,
                    'files' => $files,
                ];
            }
        }

        return $entries;
    }

    /**
     * @param array<int, array{date: string, time: string, type: string, description: string, files: string}> $entries
     */
    private function writeFile(array $entries): void
    {
        $dir = dirname($this->outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [];
        $lines[] = '# 变更日志（自动生成，勿手动编辑）';
        $lines[] = '';
        $lines[] = '> 最后更新: ' . date('Y-m-d H:i:s');
        $lines[] = '';

        $currentDate = '';
        foreach ($entries as $entry) {
            if ($entry['date'] !== $currentDate) {
                if ($currentDate !== '') {
                    $lines[] = '';
                }
                $currentDate = $entry['date'];
                $lines[] = "## {$currentDate}";
                $lines[] = '';
            }
            $lines[] = "- `{$entry['time']}` [{$entry['type']}] {$entry['description']}{$entry['files']}";
        }

        $lines[] = '';

        file_put_contents($this->outputFile, implode("\n", $lines));
    }
}
