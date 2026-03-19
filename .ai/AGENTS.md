# Phosphor AI 工作指引

> 本文件是 AI 开发者进入 Phosphor 项目的第一入口。
> 无论你是 GPT、Claude、Gemini、GLM、Qwen、豆包、MiniMax 还是其他模型，请先阅读此文件。

## 🚀 快速开始（必读）

### 第 1 步：了解项目全貌

```bash
# 如果 .ai/PROJECT_MAP.md 不存在或可能过期，先运行：
php phosphor map
```

然后阅读以下文件（按优先级排序）：

1. **`.ai/PROJECT_MAP.md`** — 项目地图：应用列表、模块清单、路由表、文件结构
2. **`.ai/ARCHITECTURE.md`** — 架构设计：请求生命周期、层级约束、命名空间
3. **`.ai/CONVENTIONS.md`** — 编码约定：严格规则、禁止项、推荐实践
4. **`.ai/PATTERNS.md`** — 设计模式：Attribute 驱动、DTO 模式、仓库模式

### 第 2 步：如果是调试任务

```
先读 .ai/ERROR_INDEX.md → 找到出错文件和行号 → 直接定位代码
再读 storage/logs/app.log → 查看详细错误上下文
```

> ⚠️ **不要遍历项目文件来找 bug！** 先看日志和错误索引，能节省 90% 的 token。

### 第 3 步：修改代码后

```bash
# 记录你做了什么变更
php phosphor changelog <类型> <描述> [文件列表...]

# 类型: NEW / MODIFY / DELETE / FIX
# 示例:
php phosphor changelog NEW '添加商品模块' apps/api/Controllers/ProductController.php
php phosphor changelog FIX '修复用户邮箱验证' src/Validation/Validator.php
```

---

## 📁 项目结构速查

```
phosphor/
├── .ai/                    ← AI 导航中心（先读这里）
│   ├── PROJECT_MAP.md      ← 项目地图（自动生成）
│   ├── CHANGELOG.md        ← 变更日志（自动追加）
│   ├── ERROR_INDEX.md      ← 错误索引（运行时自动更新）
│   ├── ARCHITECTURE.md     ← 架构设计
│   ├── CONVENTIONS.md      ← 编码约定
│   └── PATTERNS.md         ← 设计模式
├── src/                    ← 框架核心（namespace: Phosphor）
├── shared/                 ← 跨应用共享代码（namespace: Shared）
├── apps/                   ← 应用目录（namespace: Apps\{AppName}）
│   ├── api/                ← API 应用（前缀: /api）
│   └── admin/              ← 管理后台（前缀: /admin）
├── config/                 ← 配置文件
├── storage/logs/           ← 日志（JSON Lines 格式）
├── public/index.php        ← HTTP 入口
└── phosphor                ← CLI 工具（AI 专用）
```

## ⚡ 关键约定

1. **所有文件必须** `declare(strict_types=1)`
2. **路由使用 Attribute** `#[Route(Method::GET, '/path')]`，不使用配置文件
3. **依赖注入使用构造函数**，不使用 `@Inject` 或服务定位器
4. **数据传输使用 DTO**，Controller 不直接操作 Model
5. **新增模块**需在 `config/app.php` 中注册 Controller

## 🔧 CLI 命令

```bash
php phosphor map         # 重新生成项目地图
php phosphor changelog   # 记录变更
php phosphor status      # 查看项目状态
```

## 🐛 调试流程

```
1. 先读 .ai/ERROR_INDEX.md        → 找到最近的错误和位置
2. 打开对应的源文件和行号        → 直接定位问题
3. 查看 storage/logs/app.log     → 获取请求上下文和堆栈
4. 修复后运行 php phosphor changelog FIX '描述'
```
