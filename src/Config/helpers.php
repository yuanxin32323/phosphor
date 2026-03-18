<?php declare(strict_types=1);

/**
 * 读取环境变量
 */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}
