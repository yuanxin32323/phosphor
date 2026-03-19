---
description: Phosphor 框架 AI 开发工作流
---

# AI 开发者工作流

本工作流适用于所有 AI 模型在 Phosphor 框架中进行开发。

## 开始工作前

// turbo
1. 阅读 `.ai/AGENTS.md` 获取完整工作指引

// turbo
2. 阅读 `.ai/PROJECT_MAP.md` 了解项目全貌（如不存在，先运行 `php phosphor map`）

// turbo
3. 阅读 `.ai/ARCHITECTURE.md` 了解架构设计

## 调试任务

// turbo
1. 先阅读 `.ai/ERROR_INDEX.md` 定位最近错误

// turbo
2. 打开错误指向的源文件和行号

// turbo
3. 查看 `storage/logs/app.log` 获取详细上下文

## 修改代码后

// turbo
1. 运行 `php phosphor changelog <TYPE> <MESSAGE> [files...]` 记录变更

// turbo
2. 运行 `php phosphor map` 更新项目地图
