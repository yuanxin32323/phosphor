<?php declare(strict_types=1);

namespace Phosphor\Log;

/**
 * 日志级别枚举
 */
enum LogLevel: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARN = 'WARN';
    case ERROR = 'ERROR';
    case FATAL = 'FATAL';
}
