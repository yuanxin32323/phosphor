# 项目地图（自动生成，勿手动编辑）

> 最后更新: 2026-03-19 06:58:26

## 已注册应用

- **admin** (前缀: `/admin`) → `apps/admin/`
- **api** (前缀: `/api`) → `apps/api/`

## 模块清单

### admin 应用

| 模块 | Controller | Service | Repository | DTO |
|------|-----------|---------|------------|-----|
| Dashboard | [DashboardController.php](file:///apps/admin/Controllers/DashboardController.php) | - | - | - |

### api 应用

| 模块 | Controller | Service | Repository | DTO |
|------|-----------|---------|------------|-----|
| User | [UserController.php](file:///apps/api/Controllers/UserController.php) | [UserService.php](file:///apps/api/Services/UserService.php) | [UserRepository.php](file:///apps/api/Repositories/UserRepository.php) | [CreateUserInput.php](file:///apps/api/DTOs/Input/CreateUserInput.php) |

## 共享模型

| 类型 | 文件 |
|------|------|
| Model | [User.php](file:///shared/Models/User.php) |
| Enum | [UserStatus.php](file:///shared/Enums/UserStatus.php) |

## 路由表

| 方法 | 路径 | 处理方法 | 中间件 |
|------|------|---------|--------|
| GET | `/api/users` | UserController::list | - |
| POST | `/api/users` | UserController::create | - |
| GET | `/api/users/{id}` | UserController::show | - |
| GET | `/admin/dashboard` | DashboardController::index | - |

## 配置文件

- [app.php](file:///config/app.php)
- [database.php](file:///config/database.php)

## 框架核心 (`src/`)

| 模块 | 文件数 | 关键文件 |
|------|--------|---------|
| Config | 5 | [AppConfig.php](file:///src/Config/AppConfig.php) |
| Container | 1 | [Container.php](file:///src/Container/Container.php) |
| DTO | 2 | [InputDTO.php](file:///src/DTO/InputDTO.php) |
| Database | 3 | [BaseRepository.php](file:///src/Database/BaseRepository.php) |
| Event | 2 | [Event.php](file:///src/Event/Event.php) |
| Exception | 5 | [ExceptionHandler.php](file:///src/Exception/ExceptionHandler.php) |
| Http | 4 | [JsonResponse.php](file:///src/Http/JsonResponse.php) |
| Log | 3 | [ErrorIndexWriter.php](file:///src/Log/ErrorIndexWriter.php) |
| Middleware | 2 | [MiddlewareInterface.php](file:///src/Middleware/MiddlewareInterface.php) |
| Model | 3 | [BaseModel.php](file:///src/Model/BaseModel.php) |
| Navigation | 2 | [ChangelogWriter.php](file:///src/Navigation/ChangelogWriter.php) |
| Routing | 4 | [Middleware.php](file:///src/Routing/Middleware.php) |
| Validation | 5 | [Email.php](file:///src/Validation/Rule/Email.php) |
| (核心) | 1 | [Kernel.php](file:///src/Kernel.php) |
