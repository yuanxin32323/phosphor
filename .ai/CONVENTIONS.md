# Phosphor 编码约定

> 此文件由 Phosphor 框架维护，供 AI 遵循。

## 严格规则

1. 所有 PHP 文件必须声明 `declare(strict_types=1)`
2. 所有公开方法必须有参数类型和返回类型声明
3. 所有属性必须有类型声明
4. 一个文件只包含一个类/接口/枚举
5. 文件名 = 类名（PSR-4）

## 禁止事项

- ❌ 禁止使用关联数组传递结构化数据（用 DTO）
- ❌ 禁止使用 `mixed` 类型（框架底层除外）
- ❌ 禁止全局辅助函数（除 `env()`）
- ❌ 禁止魔术方法 `__call`, `__get`, `__set`
- ❌ 禁止 Controller 直接调用 Repository
- ❌ 禁止数据库查询出现在 Repository 以外的地方

## 推荐做法

- ✅ 使用构造函数 `readonly` 属性注入依赖
- ✅ 使用 Enum 替代字符串常量
- ✅ 使用 Attribute 声明路由和验证规则
- ✅ 使用 `?Type` 或 `throw` 处理空值，不返回 `null` 加 `mixed`
- ✅ 每个 Service 方法做一件事

## 文件组织

```
apps/{app}/
├── Controllers/    # 只处理 HTTP 输入/输出转换
├── Services/       # 业务逻辑
├── Repositories/   # 数据访问
├── DTOs/
│   ├── Input/      # 请求输入
│   └── Output/     # 响应输出
├── Middleware/      # 请求拦截
├── Events/         # 事件定义
└── Listeners/      # 事件处理
```
