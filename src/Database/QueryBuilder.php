<?php declare(strict_types=1);

namespace Phosphor\Database;

/**
 * 查询构建器
 *
 * 提供类型安全的链式 API 构建 SQL 查询。
 */
class QueryBuilder
{
    private string $table;
    private Connection $connection;

    /** @var string[] */
    private array $selects = ['*'];

    /** @var array<int, array{column: string, operator: string, value: mixed}> */
    private array $wheres = [];

    /** @var array<int, array{column: string, direction: string}> */
    private array $orders = [];

    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * @param string[] $columns
     */
    public function select(array $columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = ['column' => $column, 'operator' => $operator, 'value' => $value];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * 执行查询，返回所有行
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        [$sql, $bindings] = $this->buildSelect();
        return $this->connection->query($sql, $bindings);
    }

    /**
     * 执行查询，返回第一行
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        [$sql, $bindings] = $this->buildSelect();
        return $this->connection->queryFirst($sql, $bindings);
    }

    /**
     * 返回记录数
     */
    public function count(): int
    {
        $this->selects = ['COUNT(*) as cnt'];
        [$sql, $bindings] = $this->buildSelect();
        $row = $this->connection->queryFirst($sql, $bindings);
        return $row !== null ? (int) $row['cnt'] : 0;
    }

    /**
     * 插入一行数据
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $this->connection->execute($sql, array_values($data));
        return $this->connection->lastInsertId();
    }

    /**
     * 更新匹配的行
     *
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $sets = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $sets));

        [$whereSql, $whereBindings] = $this->buildWhereClause();
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
            $bindings = array_merge($bindings, $whereBindings);
        }

        return $this->connection->execute($sql, $bindings);
    }

    /**
     * 删除匹配的行
     */
    public function delete(): int
    {
        $sql = sprintf('DELETE FROM %s', $this->table);

        [$whereSql, $whereBindings] = $this->buildWhereClause();
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        return $this->connection->execute($sql, $whereBindings);
    }

    /**
     * @return array{string, array<int, mixed>}
     */
    private function buildSelect(): array
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->selects), $this->table);
        $bindings = [];

        [$whereSql, $whereBindings] = $this->buildWhereClause();
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
            $bindings = array_merge($bindings, $whereBindings);
        }

        if ($this->orders !== []) {
            $orderParts = array_map(
                fn(array $o): string => "{$o['column']} {$o['direction']}",
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return [$sql, $bindings];
    }

    /**
     * @return array{string, array<int, mixed>}
     */
    private function buildWhereClause(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $parts = [];
        $bindings = [];
        foreach ($this->wheres as $where) {
            $parts[] = "{$where['column']} {$where['operator']} ?";
            $bindings[] = $where['value'];
        }

        return [implode(' AND ', $parts), $bindings];
    }
}
