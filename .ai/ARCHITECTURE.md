# Phosphor 架构说明

> 此文件由 Phosphor 框架维护，供 AI 阅读以理解项目架构。

## 请求生命周期

```
HTTP Request
  → public/index.php
  → Kernel::handle(Request)
    → 根据 URL 前缀匹配 App (AppDefinition)
    → App 级中间件管道
    → Router::match() (基于 Controller #[Route] Attribute)
    → 方法级中间件管道
    → Controller::method(InputDTO)
      → Service::businessLogic()
        → Repository::dataAccess()
    → JsonResponse
  → Response::send()
```

## 层级约束

| 层级 | 可调用 | 禁止调用 |
|------|--------|---------|
| Controller | Service, DTO | Repository, Model 直接操作 |
| Service | Repository, 其他 Service, EventDispatcher | Controller |
| Repository | QueryBuilder, Connection | Service, Controller |

## 命名空间映射

| 命名空间 | 目录 | 用途 |
|----------|------|------|
| `Phosphor\` | `src/` | 框架核心代码 |
| `Shared\` | `shared/` | 跨应用共享代码 |
| `Apps\{AppName}\` | `apps/{app_name}/` | 各应用独立代码 |

## 命名约定

| 类型 | 后缀 | 示例 |
|------|------|------|
| 控制器 | Controller | UserController |
| 服务 | Service | UserService |
| 仓库 | Repository | UserRepository |
| 输入 DTO | Input | CreateUserInput |
| 输出 DTO | Output | UserOutput |
| 事件 | Event | UserCreatedEvent |
| 中间件 | Middleware | AuthMiddleware |
| 枚举 | (业务名) | UserStatus |

## 多应用模式

- 应用在 `config/app.php` 中显式注册
- 每个应用有独立的 URL 前缀和中间件栈
- 共享代码放在 `shared/` 目录
- 路由路径相对于应用前缀

## AI 调试路径

```
1. 读 .ai/ERROR_INDEX.md → 获取错误位置 (file:line)
2. 读目标文件的具体行 → 理解错误上下文
3. 读 storage/logs/app.log 的对应 request_id → 获取完整请求链路
4. 修复代码
```
