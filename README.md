# Phosphor

> ✨ 光的携带者 — AI 驱动的 PHP 框架核心

Phosphor 是为 **AI + 人类协作** 设计的 PHP 框架。这是框架核心包，提供路由、DI 容器、ORM、日志等基础设施。

## 安装

### 创建新项目（推荐）

```bash
composer create-project phosphor/skeleton my-project
cd my-project
php -S localhost:8080 -t public/
```

### 作为依赖引用

```bash
composer require phosphor/framework
```

## 核心组件

| 组件 | 命名空间 | 功能 |
|------|---------|------|
| HTTP | `Phosphor\Http` | Request / Response / JsonResponse |
| 路由 | `Phosphor\Routing` | Attribute 路由 + 中间件 |
| 容器 | `Phosphor\Container` | 构造函数自动注入 DI |
| ORM | `Phosphor\Database` | QueryBuilder + BaseRepository |
| 模型 | `Phosphor\Model` | Schema Attribute (#[Table], #[Column]) |
| DTO | `Phosphor\DTO` | InputDTO / OutputDTO |
| 验证 | `Phosphor\Validation` | Attribute 验证规则 |
| 日志 | `Phosphor\Log` | 分层日志 (debug.log + app.log) |
| 导航 | `Phosphor\Navigation` | ProjectMapWriter + ChangelogWriter |
| 异常 | `Phosphor\Exception` | 类型化异常 + ExceptionHandler |
| 事件 | `Phosphor\Event` | EventDispatcher |
| 内核 | `Phosphor\Kernel` | 多应用请求生命周期 |

## AI 友好设计

- **`.ai/` 目录** — AI 导航中心（项目地图、变更日志、错误索引）
- **分层日志** — debug.log 只保留关键调试信息，AI 一眼看完
- **模式开关** — `framework` / `business` 模式控制 AI 可修改范围
- **全模型入口** — 支持 Cursor / Claude / Gemini / Copilot / GLM / Qwen 等

## 许可证

MIT License
