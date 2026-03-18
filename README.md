# Phosphor

> ✨ 光的携带者 — AI 驱动的 PHP 框架

Phosphor 是一个为 **AI + 人类协作** 设计的 PHP 框架。用户只需自然语言描述需求，AI 负责生成、修改和调试代码。

## 核心特性

- 🧬 **Schema 驱动** — 定义 YAML Schema，自动生成完整模块代码
- 🔍 **智能日志** — 结构化 JSON 日志 + 错误索引，AI 先看日志再定位文件
- 🗺️ **AI 导航** — 自动生成项目地图和变更日志，避免 AI 遍历代码
- 📐 **显式设计** — Attribute 路由、类型化 DTO、构造函数注入，零魔术方法
- 🏗️ **多应用** — 一个安装承载多个应用（API、Admin 等），共享模型和服务

## 快速开始

```bash
# 安装依赖
composer install

# 生成模块
php phosphor generate user --app=api

# 启动开发服务器
php -S localhost:8080 -t public/
```

## 目录结构

```
phosphor/
├── src/          # 框架核心 (Phosphor\)
├── shared/       # 跨应用共享 (Shared\)
├── apps/         # 多应用 (Apps\)
├── schemas/      # YAML 数据模型定义
├── config/       # 配置文件
├── storage/logs/ # 结构化日志
├── public/       # Web 入口
└── .ai/          # AI 导航中心
```

## 设计哲学

1. **可追踪性** — 通过类型系统和显式引用追踪所有相关代码
2. **可预测性** — 严格约定，根据文件位置和命名即可预判内容
3. **自描述性** — 代码即文档，Attribute 即配置，DTO 即 Schema

## 许可证

MIT License
