<?php declare(strict_types=1);

namespace Phosphor\Event;

/**
 * 事件基类
 */
abstract class Event
{
    public readonly string $occurredAt;

    public function __construct()
    {
        $this->occurredAt = date('c');
    }
}
