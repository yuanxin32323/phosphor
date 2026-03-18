# Phosphor 设计模式

> 此文件供 AI 理解框架中使用的设计模式。

## 1. Attribute 驱动配置

所有元数据通过 PHP 8 Attribute 声明，而非外部配置文件：

```php
#[Route(Method::GET, '/users')]           // 路由
#[Middleware(AuthMiddleware::class)]       // 中间件
#[Column(type: 'varchar', length: 255)]   // 数据库字段
#[Rule\Required]                          // 验证规则
```

## 2. 显式构造函数注入

所有依赖通过构造函数注入，使用 `readonly` 修饰：

```php
public function __construct(
    private readonly UserRepository $userRepository,
    private readonly EventDispatcher $eventDispatcher,
) {}
```

## 3. DTO 模式

输入和输出都使用类型化的 DTO，绝不使用关联数组：

```php
// 输入 DTO - 带验证规则
class CreateUserInput extends InputDTO {
    #[Rule\Required]
    public string $name;
}

// 输出 DTO - 带转换逻辑
class UserOutput extends OutputDTO {
    public static function from(User $user): self { ... }
}
```

## 4. Repository 模式

数据访问封装在 Repository 中，使用泛型基类：

```php
class UserRepository extends BaseRepository {
    protected function getModelClass(): string {
        return User::class;
    }
}
```

## 5. Schema 驱动

数据模型由 YAML Schema 定义，代码由生成器自动创建：

```
schemas/user.yaml → php phosphor generate user → 完整模块代码
```
