<?php declare(strict_types=1);

namespace Phosphor\Event;

/**
 * 事件分发器
 *
 * 显式注册监听器，按事件类名分发。
 */
class EventDispatcher
{
    /** @var array<string, callable[]> 事件类名 → 监听器列表 */
    private array $listeners = [];

    /**
     * 注册事件监听器
     *
     * @param class-string<Event> $eventClass
     * @param callable(Event): void $listener
     */
    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * 分发事件
     */
    public function dispatch(Event $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            $listener($event);
        }
    }
}
