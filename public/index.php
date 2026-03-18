<?php declare(strict_types=1);

/**
 * Phosphor 应用入口
 *
 * 所有 HTTP 请求统一通过此文件处理。
 */

// Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

use Phosphor\Http\Request;
use Phosphor\Kernel;

// 初始化内核
$basePath = dirname(__DIR__);
$kernel = new Kernel($basePath);

// 初始化数据库表（首次运行时自动创建）
$container = $kernel->getContainer();
/** @var \Apps\Api\Repositories\UserRepository $userRepo */
$userRepo = $container->make(\Apps\Api\Repositories\UserRepository::class);
$userRepo->ensureTable();

// 捕获请求并处理
$request = Request::capture();
$response = $kernel->handle($request);

// 发送响应
$response->send();
