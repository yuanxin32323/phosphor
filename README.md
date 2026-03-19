# Phosphor

> ✨ 光的携带者 — AI 驱动的 PHP 框架

Phosphor 是一个为 **AI + 人类协作** 设计的 PHP 框架。用户只需自然语言描述需求，AI 负责生成、修改和调试代码。

## ⚡ AI 开发者？请先读这里

> **无论你是 GPT、Claude、Gemini、GLM（智谱）、Qwen（通义千问）、豆包、MiniMax 还是其他 AI 模型，请先阅读 [`.ai/AGENTS.md`](.ai/AGENTS.md) 获取完整工作指引。**

快速入口：
1. `php phosphor map` → 生成 `.ai/PROJECT_MAP.md`（项目地图）
2. `.ai/ERROR_INDEX.md` → 调试入口（运行时自动更新）
3. `php phosphor changelog` → 记录变更

## 核心特性

- 🔍 **智能日志** — 结构化 JSON 日志 + 错误索引，AI 先看日志再定位文件
- 🗺️ **AI 导航** — 项目地图 + 变更日志 + 错误索引，AI 无需遍历代码
- 📐 **显式设计** — Attribute 路由、类型化 DTO、构造函数注入，零魔术方法
- 🏗️ **多应用** — 一个安装承载多个应用（API、Admin 等），共享模型和服务
- 🤖 **全模型兼容** — 内置 Cursor / Claude / Gemini / Copilot / GLM / Qwen 入口

## 快速开始

```bash
composer install
php -S localhost:8080 -t public/

# 测试
curl http://localhost:8080/api/users
curl http://localhost:8080/admin/dashboard
```

## 目录结构

```
phosphor/
├── .ai/          # AI 导航中心（AI 先读这里）
│   ├── AGENTS.md         # AI 工作指引（核心入口）
│   ├── PROJECT_MAP.md    # 项目地图（自动生成）
│   ├── CHANGELOG.md      # 变更日志（自动追加）
│   └── ERROR_INDEX.md    # 错误索引（运行时更新）
├── src/          # 框架核心 (Phosphor\)
├── shared/       # 跨应用共享 (Shared\)
├── apps/         # 多应用 (Apps\)
├── config/       # 配置文件
├── storage/logs/ # 结构化日志
├── public/       # Web 入口
├── phosphor      # CLI 工具
├── AGENTS.md     # AI 入口（通用）
├── CLAUDE.md     # AI 入口（Claude）
└── .cursorrules  # AI 入口（Cursor）
```

## 支持的 AI 工具入口

| 文件 | 自动读取者 |
|------|-----------|
| `.ai/AGENTS.md` | 核心指引（所有 AI 模型） |
| `AGENTS.md` | 通用约定（Windsurf, Cline 等） |
| `CLAUDE.md` | Claude / Anthropic |
| `.cursorrules` | Cursor |
| `.agents/workflows/` | Gemini / Antigravity |
| `.github/copilot-instructions.md` | GitHub Copilot |
| `README.md` | GLM / Qwen / 豆包 / MiniMax / 通用 |

> 对于 API 调用型 AI（GLM、Qwen、豆包、MiniMax），建议在 System Prompt 中加入："请先阅读项目的 `.ai/AGENTS.md` 文件"

## 设计哲学

1. **可追踪性** — 通过类型系统和显式引用追踪所有相关代码
2. **可预测性** — 严格约定，根据文件位置和命名即可预判内容
3. **自描述性** — 代码即文档，Attribute 即配置，DTO 即 Schema

## 许可证

MIT License
