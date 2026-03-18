<?php declare(strict_types=1);

namespace Phosphor\DTO;

/**
 * 输入 DTO 基类
 *
 * 从请求数据自动填充属性。子类用验证 Attribute 声明规则。
 */
abstract class InputDTO
{
    /**
     * 从关联数组创建 DTO
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $dto = new static();
        $reflection = new \ReflectionClass($dto);

        foreach ($reflection->getProperties() as $prop) {
            $name = $prop->getName();
            if (array_key_exists($name, $data)) {
                $prop->setValue($dto, $data[$name]);
            }
        }

        return $dto;
    }

    /**
     * 转为关联数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $prop) {
            if ($prop->isInitialized($this)) {
                $data[$prop->getName()] = $prop->getValue($this);
            }
        }
        return $data;
    }
}
