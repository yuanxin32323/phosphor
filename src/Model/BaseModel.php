<?php declare(strict_types=1);

namespace Phosphor\Model;

/**
 * 模型基类
 *
 * 所有数据模型继承此类，并使用 #[Table] 和 #[Column] Attribute 声明 Schema。
 */
abstract class BaseModel
{
    /**
     * 从关联数组填充模型属性
     *
     * @param array<string, mixed> $data
     */
    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * 将模型转为关联数组（排除 hidden 字段）
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $data = [];

        foreach ($reflection->getProperties() as $prop) {
            $columnAttrs = $prop->getAttributes(Column::class);
            $isHidden = false;

            if ($columnAttrs !== []) {
                /** @var Column $column */
                $column = $columnAttrs[0]->newInstance();
                $isHidden = $column->hidden;
            }

            if (!$isHidden && $prop->isInitialized($this)) {
                $value = $prop->getValue($this);
                // 处理枚举值
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }
                // 处理 DateTimeImmutable
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $data[$prop->getName()] = $value;
            }
        }

        return $data;
    }
}
