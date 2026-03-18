<?php declare(strict_types=1);

namespace Phosphor\DTO;

use Phosphor\Model\BaseModel;

/**
 * 输出 DTO 基类
 *
 * 将 Model 数据转换为 API 响应格式。子类实现 from() 定义映射逻辑。
 */
abstract class OutputDTO implements \JsonSerializable
{
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
                $value = $prop->getValue($this);
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $data[$prop->getName()] = $value;
            }
        }
        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * 从模型集合创建 DTO 集合
     *
     * @param BaseModel[] $models
     * @return static[]
     */
    public static function fromCollection(array $models): array
    {
        return array_map(fn(BaseModel $model) => static::from($model), $models);
    }

    /**
     * 从模型创建 DTO（子类必须实现）
     */
    abstract public static function from(BaseModel $model): static;
}
