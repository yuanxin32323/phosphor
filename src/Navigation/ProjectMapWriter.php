<?php declare(strict_types=1);

namespace Phosphor\Navigation;

use Phosphor\Routing\Router;
use ReflectionClass;

/**
 * 项目地图生成器
 *
 * 自动扫描项目结构，生成 .ai/PROJECT_MAP.md。
 * AI 打开项目时第一个读此文件，无需扫描目录即可了解整个项目。
 *
 * 触发时机：Kernel 启动时自动执行。
 */
class ProjectMapWriter
{
    private string $outputFile;
    private string $basePath;
    private Router $router;

    public function __construct(string $outputFile, string $basePath, Router $router)
    {
        $this->outputFile = $outputFile;
        $this->basePath = rtrim($basePath, '/');
        $this->router = $router;
    }

    /**
     * 生成项目地图
     */
    public function generate(): void
    {
        $lines = [];
        $lines[] = '# 项目地图（自动生成，勿手动编辑）';
        $lines[] = '';
        $lines[] = '> 最后更新: ' . date('Y-m-d H:i:s');
        $lines[] = '';

        // 1. 应用列表
        $lines[] = '## 已注册应用';
        $lines[] = '';
        $apps = $this->scanApps();
        if ($apps === []) {
            $lines[] = '_暂无注册应用_';
        }
        foreach ($apps as $app) {
            $lines[] = "- **{$app['name']}** (前缀: `{$app['prefix']}`) → `{$app['dir']}/`";
        }
        $lines[] = '';

        // 2. 模块清单
        $lines[] = '## 模块清单';
        $lines[] = '';
        foreach ($apps as $app) {
            $lines[] = "### {$app['name']} 应用";
            $lines[] = '';
            $modules = $this->scanModules($app['dir']);
            if ($modules === []) {
                $lines[] = '_暂无模块_';
            } else {
                $lines[] = '| 模块 | Controller | Service | Repository | DTO |';
                $lines[] = '|------|-----------|---------|------------|-----|';
                foreach ($modules as $module) {
                    $lines[] = sprintf(
                        '| %s | %s | %s | %s | %s |',
                        $module['name'],
                        $this->fileLink($module['controller']),
                        $this->fileLink($module['service']),
                        $this->fileLink($module['repository']),
                        $this->fileLink($module['dto']),
                    );
                }
            }
            $lines[] = '';
        }

        // 3. 共享模型
        $lines[] = '## 共享模型';
        $lines[] = '';
        $sharedModels = $this->scanDirectory('shared/Models', '.php');
        $sharedEnums = $this->scanDirectory('shared/Enums', '.php');
        if ($sharedModels !== [] || $sharedEnums !== []) {
            $lines[] = '| 类型 | 文件 |';
            $lines[] = '|------|------|';
            foreach ($sharedModels as $file) {
                $lines[] = "| Model | {$this->fileLink($file)} |";
            }
            foreach ($sharedEnums as $file) {
                $lines[] = "| Enum | {$this->fileLink($file)} |";
            }
        } else {
            $lines[] = '_暂无共享模型_';
        }
        $lines[] = '';

        // 4. 路由表
        $lines[] = '## 路由表';
        $lines[] = '';
        $routes = $this->router->getRegisteredRoutes();
        if ($routes !== []) {
            $lines[] = '| 方法 | 路径 | 处理方法 | 中间件 |';
            $lines[] = '|------|------|---------|--------|';
            foreach ($routes as $route) {
                $middlewares = $route['middlewares'] !== [] ? implode(', ', $route['middlewares']) : '-';
                $lines[] = "| {$route['method']} | `{$route['path']}` | {$route['controller']} | {$middlewares} |";
            }
        } else {
            $lines[] = '_暂无路由_';
        }
        $lines[] = '';

        // 5. 配置文件
        $lines[] = '## 配置文件';
        $lines[] = '';
        $configFiles = $this->scanDirectory('config', '.php');
        foreach ($configFiles as $file) {
            $lines[] = "- {$this->fileLink($file)}";
        }
        $lines[] = '';

        // 6. 框架核心概览
        $lines[] = '## 框架核心 (`src/`)';
        $lines[] = '';
        $srcDirs = $this->scanSubDirs('src');
        $lines[] = '| 模块 | 文件数 | 关键文件 |';
        $lines[] = '|------|--------|---------|';
        foreach ($srcDirs as $dir) {
            $files = $this->scanDirectory('src/' . $dir, '.php');
            $keyFile = $files[0] ?? '-';
            $lines[] = "| {$dir} | " . count($files) . " | {$this->fileLink($keyFile)} |";
        }
        // 顶层文件
        $topFiles = $this->scanDirectory('src', '.php', false);
        foreach ($topFiles as $file) {
            $lines[] = "| (核心) | 1 | {$this->fileLink($file)} |";
        }
        $lines[] = '';

        $this->writeFile($lines);
    }

    /**
     * 扫描已注册的应用
     *
     * @return array<int, array{name: string, prefix: string, dir: string}>
     */
    private function scanApps(): array
    {
        $apps = [];
        $appsDir = $this->basePath . '/apps';
        if (!is_dir($appsDir)) {
            return $apps;
        }

        $entries = scandir($appsDir);
        if ($entries === false) {
            return $apps;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $fullPath = $appsDir . '/' . $entry;
            if (is_dir($fullPath)) {
                $apps[] = [
                    'name' => $entry,
                    'prefix' => '/' . $entry,
                    'dir' => 'apps/' . $entry,
                ];
            }
        }

        return $apps;
    }

    /**
     * 扫描应用内的模块（按 Controller 推导）
     *
     * @return array<int, array{name: string, controller: string, service: string, repository: string, dto: string}>
     */
    private function scanModules(string $appDir): array
    {
        $modules = [];
        $controllersDir = $this->basePath . '/' . $appDir . '/Controllers';

        if (!is_dir($controllersDir)) {
            return $modules;
        }

        $files = scandir($controllersDir);
        if ($files === false) {
            return $modules;
        }

        foreach ($files as $file) {
            if (!str_ends_with($file, 'Controller.php')) {
                continue;
            }

            $moduleName = str_replace('Controller.php', '', $file);
            $modules[] = [
                'name' => $moduleName,
                'controller' => $appDir . '/Controllers/' . $file,
                'service' => $this->findFile($appDir . '/Services/' . $moduleName . 'Service.php'),
                'repository' => $this->findFile($appDir . '/Repositories/' . $moduleName . 'Repository.php'),
                'dto' => $this->findFile($appDir . '/DTOs/Input/Create' . $moduleName . 'Input.php'),
            ];
        }

        return $modules;
    }

    /**
     * 扫描目录下的文件
     *
     * @return string[]
     */
    private function scanDirectory(string $relativeDir, string $extension, bool $recursive = true): array
    {
        $fullDir = $this->basePath . '/' . $relativeDir;
        if (!is_dir($fullDir)) {
            return [];
        }

        $results = [];
        $entries = scandir($fullDir);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $fullPath = $fullDir . '/' . $entry;
            $relativePath = $relativeDir . '/' . $entry;

            if (is_file($fullPath) && str_ends_with($entry, $extension)) {
                $results[] = $relativePath;
            } elseif ($recursive && is_dir($fullPath)) {
                $results = array_merge($results, $this->scanDirectory($relativePath, $extension, true));
            }
        }

        return $results;
    }

    /**
     * 扫描子目录名
     *
     * @return string[]
     */
    private function scanSubDirs(string $relativeDir): array
    {
        $fullDir = $this->basePath . '/' . $relativeDir;
        if (!is_dir($fullDir)) {
            return [];
        }

        $dirs = [];
        $entries = scandir($fullDir);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($fullDir . '/' . $entry)) {
                $dirs[] = $entry;
            }
        }

        sort($dirs);
        return $dirs;
    }

    private function findFile(string $relativePath): string
    {
        $fullPath = $this->basePath . '/' . $relativePath;
        return file_exists($fullPath) ? $relativePath : '-';
    }

    private function fileLink(string $relativePath): string
    {
        if ($relativePath === '-') {
            return '-';
        }
        $basename = basename($relativePath);
        return "[{$basename}](file:///{$relativePath})";
    }

    /**
     * @param string[] $lines
     */
    private function writeFile(array $lines): void
    {
        $dir = dirname($this->outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->outputFile, implode("\n", $lines));
    }
}
