<?php declare(strict_types=1);

namespace Phosphor\Database;

use Phosphor\Model\BaseModel;
use Phosphor\Model\Column;
use Phosphor\Model\Table;
use ReflectionClass;

/**
 * 仓库基类
 *
 * 提供类型安全的 CRUD 操作。子类只需声明 getModelClass() 即可获得完整数据访问能力。
 *
 * @template T of BaseModel
 */
abstract class BaseRepository
{
    private string $tableName;

    /** @var ReflectionClass<T> */
    private ReflectionClass $modelReflection;

    public function __construct(
        protected readonly Connection $connection,
    ) {
        $modelClass = $this->getModelClass();
        $this->modelReflection = new ReflectionClass($modelClass);
        $this->tableName = $this->resolveTableName();
    }

    /**
     * 子类必须声明模型类
     *
     * @return class-string<T>
     */
    abstract protected function getModelClass(): string;

    /**
     * 获取查询构建器
     */
    protected function query(): QueryBuilder
    {
        return new QueryBuilder($this->connection, $this->tableName);
    }

    /**
     * 通过主键 ID 查找
     *
     * @return T|null
     */
    public function findById(int|string $id): ?BaseModel
    {
        $primaryKey = $this->getPrimaryKeyColumn();
        $row = $this->query()->where($primaryKey, '=', $id)->first();

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * 查找所有记录
     *
     * @return T[]
     */
    public function findAll(): array
    {
        $rows = $this->query()->all();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    /**
     * 保存模型（INSERT 或 UPDATE）
     *
     * @param T $model
     */
    public function save(BaseModel $model): void
    {
        $primaryKey = $this->getPrimaryKeyColumn();
        $data = $model->toArray();

        // 处理自动时间戳
        $now = date('Y-m-d H:i:s');
        foreach ($this->modelReflection->getProperties() as $prop) {
            $columnAttrs = $prop->getAttributes(Column::class);
            if ($columnAttrs === []) {
                continue;
            }
            /** @var Column $column */
            $column = $columnAttrs[0]->newInstance();
            $propName = $prop->getName();

            if ($column->autoUpdate) {
                $data[$propName] = $now;
                $model->{$propName} = new \DateTimeImmutable($now);
            }
        }

        // 判断是插入还是更新
        if (isset($data[$primaryKey]) && $data[$primaryKey] !== null && $data[$primaryKey] !== 0) {
            // UPDATE
            $id = $data[$primaryKey];
            unset($data[$primaryKey]);
            // 移除 autoCreate 字段
            foreach ($this->modelReflection->getProperties() as $prop) {
                $columnAttrs = $prop->getAttributes(Column::class);
                if ($columnAttrs !== []) {
                    /** @var Column $column */
                    $column = $columnAttrs[0]->newInstance();
                    if ($column->autoCreate) {
                        unset($data[$prop->getName()]);
                    }
                }
            }
            $this->query()->where($primaryKey, '=', $id)->update($data);
        } else {
            // INSERT
            unset($data[$primaryKey]);
            // 处理自动创建时间
            foreach ($this->modelReflection->getProperties() as $prop) {
                $columnAttrs = $prop->getAttributes(Column::class);
                if ($columnAttrs !== []) {
                    /** @var Column $column */
                    $column = $columnAttrs[0]->newInstance();
                    if ($column->autoCreate && !isset($data[$prop->getName()])) {
                        $data[$prop->getName()] = $now;
                    }
                }
            }
            $id = $this->query()->insert($data);
            // 回写 ID
            $prop = $this->modelReflection->getProperty($primaryKey);
            if ($prop->isReadOnly()) {
                // readonly 属性需要通过反射设置
                $prop->setValue($model, (int) $id);
            } else {
                $model->{$primaryKey} = (int) $id;
            }
        }
    }

    /**
     * 删除模型
     *
     * @param T $model
     */
    public function delete(BaseModel $model): void
    {
        $primaryKey = $this->getPrimaryKeyColumn();
        $data = $model->toArray();
        if (isset($data[$primaryKey])) {
            $this->query()->where($primaryKey, '=', $data[$primaryKey])->delete();
        }
    }

    /**
     * 确保数据库表存在（基于 Model Schema 自动创建）
     */
    public function ensureTable(): void
    {
        $columns = [];
        $primaryKey = null;

        foreach ($this->modelReflection->getProperties() as $prop) {
            $columnAttrs = $prop->getAttributes(Column::class);
            if ($columnAttrs === []) {
                continue;
            }
            /** @var Column $column */
            $column = $columnAttrs[0]->newInstance();
            $name = $prop->getName();

            $sqlType = $this->mapColumnType($column);
            $parts = ["\"{$name}\" {$sqlType}"];

            if ($column->primary) {
                $primaryKey = $name;
                if ($column->autoIncrement) {
                    // SQLite 使用 INTEGER PRIMARY KEY 实现自增
                    $parts = ["\"{$name}\" INTEGER PRIMARY KEY AUTOINCREMENT"];
                } else {
                    $parts[] = 'PRIMARY KEY';
                }
            } else {
                if (!$column->nullable) {
                    $parts[] = 'NOT NULL';
                }
                if ($column->unique) {
                    $parts[] = 'UNIQUE';
                }
                if ($column->default !== null) {
                    if ($column->default instanceof \BackedEnum) {
                        $defaultVal = "'{$column->default->value}'";
                    } elseif (is_string($column->default)) {
                        $defaultVal = "'{$column->default}'";
                    } else {
                        $defaultVal = (string) $column->default;
                    }
                    $parts[] = "DEFAULT {$defaultVal}";
                }
            }

            $columns[] = implode(' ', $parts);
        }

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS "%s" (%s)',
            $this->tableName,
            implode(', ', $columns)
        );

        $this->connection->execute($sql);
    }

    /**
     * 将数据库行转换为模型实例
     *
     * @param array<string, mixed> $row
     * @return T
     */
    private function hydrate(array $row): BaseModel
    {
        $model = $this->modelReflection->newInstanceWithoutConstructor();

        foreach ($this->modelReflection->getProperties() as $prop) {
            $name = $prop->getName();
            if (!array_key_exists($name, $row)) {
                continue;
            }

            $value = $row[$name];

            // 检查 Column Attribute 以确定类型转换
            $columnAttrs = $prop->getAttributes(Column::class);
            if ($columnAttrs !== []) {
                /** @var Column $column */
                $column = $columnAttrs[0]->newInstance();

                // 枚举转换
                if ($column->enumClass !== null && is_string($value)) {
                    $enumClass = $column->enumClass;
                    if (enum_exists($enumClass)) {
                        $value = $enumClass::from($value);
                    }
                }

                // 时间戳转换
                if (in_array($column->type, ['timestamp', 'datetime'], true) && is_string($value)) {
                    $value = new \DateTimeImmutable($value);
                }
            }

            $prop->setValue($model, $value);
        }

        return $model;
    }

    private function resolveTableName(): string
    {
        $tableAttrs = $this->modelReflection->getAttributes(Table::class);
        if ($tableAttrs !== []) {
            /** @var Table $table */
            $table = $tableAttrs[0]->newInstance();
            return $table->name;
        }
        // 默认使用类名小写复数
        return strtolower($this->modelReflection->getShortName()) . 's';
    }

    private function getPrimaryKeyColumn(): string
    {
        foreach ($this->modelReflection->getProperties() as $prop) {
            $columnAttrs = $prop->getAttributes(Column::class);
            if ($columnAttrs !== []) {
                /** @var Column $column */
                $column = $columnAttrs[0]->newInstance();
                if ($column->primary) {
                    return $prop->getName();
                }
            }
        }
        return 'id';
    }

    private function mapColumnType(Column $column): string
    {
        return match ($column->type) {
            'bigint', 'int', 'integer' => 'INTEGER',
            'varchar', 'string', 'email' => "VARCHAR({$column->length})",
            'text' => 'TEXT',
            'enum' => "VARCHAR(50)",
            'timestamp', 'datetime' => 'DATETIME',
            'boolean', 'bool' => 'BOOLEAN',
            'float', 'double', 'decimal' => 'REAL',
            default => 'TEXT',
        };
    }
}
