<?php declare(strict_types=1);

namespace Shared\Enums;

/**
 * 用户状态枚举
 */
enum UserStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Banned = 'banned';
}
