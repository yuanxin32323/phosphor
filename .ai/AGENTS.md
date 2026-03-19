# Phosphor AI 工作指引

> 本文件是 AI 进入 Phosphor 项目的唯一入口。所有 AI 模型请先阅读此文件。

---

## ⚠️ 第 0 步：检查工作模式

```
读取 .ai/MODE 文件
```

| MODE 值 | 含义 | AI 可修改的范围 | 不可触碰 |
|---------|------|---------------|---------|
| `framework` | 框架开发模式 | `src/`、`.ai/rules/`、框架测试 | — |
| `business` | 业务开发模式 | `apps/`、`shared/`、`config/` | `src/`（框架核心） |

> **business 模式下，AI 不得修改 `src/` 目录中的任何文件。** 如果遇到框架 bug，应记录到 `.ai/ERROR_INDEX.md` 并报告给用户，而不是自行修改框架代码。

---

## 🚀 第 1 步：了解项目全貌

```bash
# 如果 .ai/PROJECT_MAP.md 不存在或可能过期：
php phosphor map
```

按优先级阅读：

1. **`.ai/PROJECT_MAP.md`** — 项目地图：应用、模块、路由、文件
2. **`.ai/rules/ARCHITECTURE.md`** — 架构：请求生命周期、层级约束
3. **`.ai/rules/CONVENTIONS.md`** — 编码约定：严格规则和禁止项
4. **`.ai/rules/PATTERNS.md`** — 设计模式：Attribute 驱动、DTO、仓库

## 🐛 第 2 步：调试任务

```
先读 .ai/ERROR_INDEX.md    → 最近异常的文件和行号
再读 storage/logs/debug.log → 业务调试日志（只有关键信息，限 100 条）
兜底 storage/logs/app.log   → 全量请求链路
```

> ⚠️ **不要遍历项目文件找 bug！** 先看日志，能节省 90% 的 token。

## ✏️ 第 3 步：修改代码后

```bash
php phosphor changelog <TYPE> <MESSAGE> [files...]
# TYPE: NEW / MODIFY / DELETE / FIX
```

---

## 📁 目录结构

```
.ai/
├── AGENTS.md              ← 你正在读的文件（总入口）
├── MODE                   ← 工作模式：framework 或 business
├── rules/                 ← 框架规则（AI 必须遵守，不可修改）
│   ├── ARCHITECTURE.md
│   ├── CONVENTIONS.md
│   └── PATTERNS.md
├── PROJECT_MAP.md         ← 项目地图（自动生成）
├── CHANGELOG.md           ← 变更日志（自动追加）
└── ERROR_INDEX.md         ← 错误索引（运行时自动更新）

src/                       ← 框架核心（business 模式禁止修改）
shared/                    ← 跨应用共享（两种模式均可修改）
apps/                      ← 应用代码（business 模式主要工作区）
config/                    ← 配置文件
storage/logs/
├── debug.log              ← 业务调试日志（AI 优先查看）
└── app.log                ← 全量请求日志
```

## ⚡ 关键约定

1. **所有文件**必须 `declare(strict_types=1)`
2. **路由**使用 `#[Route(Method::GET, '/path')]` Attribute
3. **依赖注入**使用构造函数，不用服务定位器
4. **数据传输**使用 DTO，Controller 不直接操作 Model
5. **新模块**需在 `config/app.php` 注册 Controller
6. **调试日志**使用 `$this->logger->debug()`，写入 debug.log

## 🔧 CLI 命令

```bash
php phosphor map         # 生成项目地图
php phosphor changelog   # 记录变更
php phosphor status      # 查看项目状态
php phosphor mode        # 查看/切换工作模式
```
