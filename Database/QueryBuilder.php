<?php

declare(strict_types=1);

namespace yurni\Database;

use InvalidArgumentException;
use yurni\Db;

class QueryBuilder
{
    private Db $db;

    private ?string $table = null;

    /** @var array<int, string> */
    private array $columns = ['*'];

    /** @var array<int, array{type: string, sql: string}> */
    private array $joins = [];

    /** @var array<int, array{boolean: string, sql: string}> */
    private array $wheres = [];

    /** @var array<int, array{boolean: string, sql: string}> */
    private array $havings = [];

    /** @var array<int, string> */
    private array $groupBys = [];

    /** @var array<int, string> */
    private array $orderBys = [];

    /** @var array<string, mixed> */
    private array $bindings = [];

    private int $bindingCounter = 0;

    private ?int $limitValue = null;

    private ?int $offsetValue = null;

    private bool $distinct = false;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function from(string $table): self
    {
        return $this->table($table);
    }

    public function select(array|string ...$columns): self
    {
        $flattened = $this->flattenColumns($columns);
        $this->columns = $flattened === [] ? ['*'] : $flattened;

        return $this;
    }

    public function addSelect(array|string ...$columns): self
    {
        $flattened = $this->flattenColumns($columns);

        if ($this->columns === ['*']) {
            $this->columns = [];
        }

        $this->columns = array_values(array_unique([...$this->columns, ...$flattened]));

        return $this;
    }

    public function distinct(bool $value = true): self
    {
        $this->distinct = $value;

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
            throw new InvalidArgumentException("Unsupported join type [{$type}].");
        }

        $this->joins[] = [
            'type' => $type,
            'sql' => sprintf(
                '%s JOIN %s ON %s %s %s',
                $type,
                $this->wrapTable($table),
                $this->wrap($first),
                $operator,
                $this->wrap($second)
            ),
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /** @var array<int, string> */
    protected array $allowedOperators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if ($value === null && in_array(strtoupper((string) $operator), ['=', 'IS'], true)) {
            return $this->whereNull($column, $boolean);
        }

        if ($value === null && in_array(strtoupper((string) $operator), ['!=', '<>', 'IS NOT'], true)) {
            return $this->whereNotNull($column, $boolean);
        }

        $operator = strtolower((string) $operator);
        if (!in_array($operator, $this->allowedOperators, true)) {
            throw new InvalidArgumentException("Unsupported operator [{$operator}].");
        }

        $placeholder = $this->bindValue($value);
        $this->wheres[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf('%s %s %s', $this->wrap($column), strtoupper($operator), $placeholder),
        ];

        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf('%s IS NULL', $this->wrap($column)),
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf('%s IS NOT NULL', $this->wrap($column)),
        ];

        return $this;
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        if ($values === []) {
            $this->wheres[] = [
                'boolean' => strtoupper($boolean),
                'sql' => $not ? '1 = 1' : '1 = 0',
            ];

            return $this;
        }

        $placeholders = [];
        foreach ($values as $value) {
            $placeholders[] = $this->bindValue($value);
        }

        $this->wheres[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf(
                '%s %sIN (%s)',
                $this->wrap($column),
                $not ? 'NOT ' : '',
                implode(', ', $placeholders)
            ),
        ];

        return $this;
    }

    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException('whereBetween expects exactly two values.');
        }

        [$from, $to] = array_values($values);
        $fromPlaceholder = $this->bindValue($from);
        $toPlaceholder = $this->bindValue($to);

        $this->wheres[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf(
                '%s %sBETWEEN %s AND %s',
                $this->wrap($column),
                $not ? 'NOT ' : '',
                $fromPlaceholder,
                $toPlaceholder
            ),
        ];

        return $this;
    }

    public function orWhereBetween(string $column, array $values): self
    {
        return $this->whereBetween($column, $values, 'OR');
    }

    public function whereNotBetween(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    public function orWhereLike(string $column, string $value): self
    {
        return $this->whereLike($column, $value, 'OR');
    }

    public function groupBy(array|string ...$columns): self
    {
        $this->groupBys = [...$this->groupBys, ...$this->flattenColumns($columns)];

        return $this;
    }

    public function having(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtolower((string) $operator);
        if (!in_array($operator, $this->allowedOperators, true)) {
            throw new InvalidArgumentException("Unsupported operator [{$operator}].");
        }

        $placeholder = $this->bindValue($value);
        $this->havings[] = [
            'boolean' => strtoupper($boolean),
            'sql' => sprintf('%s %s %s', $this->wrap($column), strtoupper($operator), $placeholder),
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Unsupported order direction [{$direction}].");
        }

        $this->orderBys[] = sprintf('%s %s', $this->wrap($column), $direction);

        return $this;
    }

    public function inRandomOrder(): self
    {
        $this->orderBys[] = 'RAND()';

        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(0, $limit);

        return $this;
    }

    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);

        return $this;
    }

    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    public function forPage(int $page, int $perPage = 15): self
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function when(mixed $value, callable $callback, ?callable $default = null): self
    {
        if ($value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }

        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function get(): array
    {
        return $this->db->select($this->toSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);

        return $clone->db->selectOne($clone->toSql(), $clone->bindings);
    }

    public function firstOrFail(): array
    {
        $result = $this->first();
        if ($result === null) {
            throw new InvalidArgumentException('No records matched the current query.');
        }

        return $result;
    }

    public function value(string $column): mixed
    {
        $alias = '__value';
        $row = (clone $this)->select("{$column} AS {$alias}")->first();

        return $row[$alias] ?? null;
    }

    public function pluck(string $column): array
    {
        $alias = '__pluck';

        return array_column((clone $this)->select("{$column} AS {$alias}")->get(), $alias);
    }

    public function count(string $column = '*'): int
    {
        $query = clone $this;
        $query->columns = ["COUNT({$column}) AS aggregate"];
        $query->orderBys = [];
        $query->limitValue = null;
        $query->offsetValue = null;

        $row = $query->first();

        return (int) ($row['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = $this->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = (clone $this)->forPage($page, $perPage)->get();

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $total === 0 ? null : (($page - 1) * $perPage) + 1,
                'to' => $total === 0 ? null : min($total, $page * $perPage),
                'has_more_pages' => $page < $lastPage,
            ],
        ];
    }

    public function insert(array $data): bool
    {
        $this->assertTable();
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($data as $value) {
            $placeholder = $this->createPlaceholder();
            $placeholders[] = $placeholder;
            $bindings[$placeholder] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapTable($this->table),
            implode(', ', array_map(fn (string $column): string => $this->wrap($column), $columns)),
            implode(', ', $placeholders)
        );

        return $this->db->affectingStatement($sql, $bindings) > 0;
    }

    public function insertGetId(array $data): int
    {
        $this->assertTable();
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($data as $value) {
            $placeholder = $this->createPlaceholder();
            $placeholders[] = $placeholder;
            $bindings[$placeholder] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapTable($this->table),
            implode(', ', array_map(fn (string $column): string => $this->wrap($column), $columns)),
            implode(', ', $placeholders)
        );

        return $this->db->insertGetId($sql, $bindings);
    }

    public function update(array $data): int
    {
        $this->assertTable();
        if ($data === []) {
            return 0;
        }

        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $placeholder = $this->createPlaceholder();
            $sets[] = sprintf('%s = %s', $this->wrap((string) $column), $placeholder);
            $bindings[$placeholder] = $value;
        }

        [$whereSql, $whereBindings] = $this->compileWheres();
        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->wrapTable($this->table),
            implode(', ', $sets),
            $whereSql
        );

        return $this->db->affectingStatement($sql, [...$bindings, ...$whereBindings]);
    }

    public function delete(): int
    {
        $this->assertTable();
        [$whereSql, $whereBindings] = $this->compileWheres();
        $sql = sprintf('DELETE FROM %s%s', $this->wrapTable($this->table), $whereSql);

        return $this->db->affectingStatement($sql, $whereBindings);
    }

    public function toSql(): string
    {
        $this->assertTable();

        $select = $this->distinct ? 'SELECT DISTINCT' : 'SELECT';
        $sql = sprintf(
            '%s %s FROM %s',
            $select,
            implode(', ', array_map(fn (string $column): string => $this->wrapSelect($column), $this->columns)),
            $this->wrapTable($this->table)
        );

        if ($this->joins !== []) {
            $sql .= ' ' . implode(' ', array_column($this->joins, 'sql'));
        }

        [$whereSql] = $this->compileWheres();
        $sql .= $whereSql;

        if ($this->groupBys !== []) {
            $sql .= ' GROUP BY ' . implode(', ', array_map(fn (string $column): string => $this->wrap($column), $this->groupBys));
        }

        [$havingSql] = $this->compileHavings();
        $sql .= $havingSql;

        if ($this->orderBys !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            if ($this->limitValue === null) {
                $sql .= ' LIMIT 18446744073709551615';
            }

            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /** @return array<string, mixed> */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    private function assertTable(): void
    {
        if ($this->table === null || $this->table === '') {
            throw new InvalidArgumentException('Query builder table has not been set.');
        }
    }

    /** @param array<int, array|string> $columns */
    private function flattenColumns(array $columns): array
    {
        $flattened = [];

        foreach ($columns as $column) {
            if (is_array($column)) {
                foreach ($column as $nested) {
                    $flattened[] = (string) $nested;
                }

                continue;
            }

            $flattened[] = (string) $column;
        }

        return array_values(array_filter($flattened, static fn (string $value): bool => $value !== ''));
    }

    private function bindValue(mixed $value): string
    {
        $placeholder = $this->createPlaceholder();
        $this->bindings[$placeholder] = $value;

        return $placeholder;
    }

    private function createPlaceholder(): string
    {
        $this->bindingCounter++;

        return ':p' . $this->bindingCounter;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function compileWheres(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $compiled = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : ' ' . $where['boolean'] . ' ';
            $compiled[] = $prefix . $where['sql'];
        }

        return [' WHERE ' . implode('', $compiled), $this->bindings];
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function compileHavings(): array
    {
        if ($this->havings === []) {
            return ['', []];
        }

        $compiled = [];
        foreach ($this->havings as $index => $having) {
            $prefix = $index === 0 ? '' : ' ' . $having['boolean'] . ' ';
            $compiled[] = $prefix . $having['sql'];
        }

        return [' HAVING ' . implode('', $compiled), $this->bindings];
    }

    private function wrap(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        if (preg_match('/\s+as\s+/i', $value) === 1) {
            [$column, $alias] = preg_split('/\s+as\s+/i', $value) ?: [$value, null];

            return $this->wrap($column) . ' AS ' . $this->wrap($alias ?? '');
        }

        if (str_contains($value, '(') || str_contains($value, ')') || str_contains($value, '`')) {
            return $value;
        }

        return implode('.', array_map(
            static fn (string $segment): string => $segment === '*' ? '*' : '`' . str_replace('`', '', $segment) . '`',
            explode('.', $value)
        ));
    }

    private function wrapTable(string $table): string
    {
        if (preg_match('/\s+as\s+/i', $table) === 1) {
            [$name, $alias] = preg_split('/\s+as\s+/i', $table) ?: [$table, null];

            return $this->wrap($name) . ' AS ' . $this->wrap($alias ?? '');
        }

        return $this->wrap($table);
    }

    private function wrapSelect(string $column): string
    {
        return $column === '*' ? '*' : $this->wrap($column);
    }
}
