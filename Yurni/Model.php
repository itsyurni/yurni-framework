<?php

declare(strict_types=1);

namespace yurni;

use BadMethodCallException;
use InvalidArgumentException;
use yurni\Database\QueryBuilder;

/**
 * الكلاس الأساسي للنماذج.
 * يوفر طبقة Model مريحة فوق Query Builder مع واجهة CRUD قياسية.
 */
abstract class Model
{
    protected Db $db;

    protected string $table = '';

    protected string $primaryKey = 'id';

    protected bool $softDeletes = false;

    protected string $deletedAtColumn = 'deleted_at';

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function db(): Db
    {
        return $this->db;
    }

    public function query(): QueryBuilder
    {
        $query = $this->db->table($this->getTable());

        if ($this->usesSoftDeletes()) {
            $query->whereNull($this->getDeletedAtColumn());
        }

        return $query;
    }

    public function newQuery(): QueryBuilder
    {
        return $this->query();
    }

    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        $class = static::class;
        $baseName = str_contains($class, '\\')
            ? substr($class, (int) strrpos($class, '\\') + 1)
            : $class;

        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));

        return str_ends_with($snake, 's') ? $snake : $snake . 's';
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    public function withTrashed(): QueryBuilder
    {
        return $this->db->table($this->getTable());
    }

    public function onlyTrashed(): QueryBuilder
    {
        return $this->withTrashed()->whereNotNull($this->getDeletedAtColumn());
    }

    /** @return array<int, array<string, mixed>> */
    public function all(array|string $columns = ['*']): array
    {
        return $this->query()->select($columns)->get();
    }

    public function find(int|string $id, array|string $columns = ['*']): ?array
    {
        return $this->query()
            ->select($columns)
            ->where($this->getPrimaryKey(), $id)
            ->first();
    }

    public function findOrFail(int|string $id, array|string $columns = ['*']): array
    {
        $record = $this->find($id, $columns);

        if ($record === null) {
            throw new InvalidArgumentException('The requested model record was not found.');
        }

        return $record;
    }

    public function create(array $attributes): int
    {
        return $this->query()->insertGetId($attributes);
    }

    public function firstOrCreate(array $conditions, array $values = []): array
    {
        $query = $this->query();
        foreach ($conditions as $column => $value) {
            $query->where((string) $column, $value);
        }

        $record = $query->first();
        if ($record !== null) {
            return $record;
        }

        $id = $this->create([...$conditions, ...$values]);

        return $this->findOrFail($id);
    }

    public function updateOrCreate(array $conditions, array $values = []): array
    {
        $query = $this->query();
        foreach ($conditions as $column => $value) {
            $query->where((string) $column, $value);
        }

        $record = $query->first();
        if ($record === null) {
            $id = $this->create([...$conditions, ...$values]);

            return $this->findOrFail($id);
        }

        $this->query()
            ->where($this->getPrimaryKey(), $record[$this->getPrimaryKey()] ?? null)
            ->update($values);

        return $this->findOrFail($record[$this->getPrimaryKey()]);
    }

    public function updateById(int|string $id, array $attributes): int
    {
        return $this->query()
            ->where($this->getPrimaryKey(), $id)
            ->update($attributes);
    }

    public function deleteById(int|string $id): int
    {
        if ($this->usesSoftDeletes()) {
            return $this->query()
                ->where($this->getPrimaryKey(), $id)
                ->update([$this->getDeletedAtColumn() => date('Y-m-d H:i:s')]);
        }

        return $this->query()
            ->where($this->getPrimaryKey(), $id)
            ->delete();
    }

    public function restore(int|string $id): int
    {
        if (!$this->usesSoftDeletes()) {
            throw new InvalidArgumentException('Restore is only available for soft deleting models.');
        }

        return $this->withTrashed()
            ->where($this->getPrimaryKey(), $id)
            ->update([$this->getDeletedAtColumn() => null]);
    }

    public function forceDeleteById(int|string $id): int
    {
        return $this->withTrashed()
            ->where($this->getPrimaryKey(), $id)
            ->delete();
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        return $this->query()->paginate($page, $perPage);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, string $ownerKey = 'id', mixed $value = null): QueryBuilder
    {
        $instance = new $related();
        if (!$instance instanceof self) {
            throw new InvalidArgumentException("Related model [{$related}] must extend " . self::class . '.');
        }

        $foreignKey ??= strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->shortName($related))) . '_id';

        return $instance->query()->where($ownerKey, $value);
    }

    public function hasMany(string $related, ?string $foreignKey = null, mixed $localValue = null, ?string $localKey = null): QueryBuilder
    {
        $instance = new $related();
        if (!$instance instanceof self) {
            throw new InvalidArgumentException("Related model [{$related}] must extend " . self::class . '.');
        }

        $localKey ??= $this->getPrimaryKey();
        $foreignKey ??= strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->shortName(static::class))) . '_id';

        return $instance->query()->where($foreignKey, $localValue);
    }

    public function hasOne(string $related, ?string $foreignKey = null, mixed $localValue = null, ?string $localKey = null): QueryBuilder
    {
        return $this->hasMany($related, $foreignKey, $localValue, $localKey)->limit(1);
    }

    public function __call(string $method, array $arguments): mixed
    {
        $query = $this->query();

        if (!method_exists($query, $method)) {
            throw new BadMethodCallException(sprintf('Method [%s] does not exist on model [%s].', $method, static::class));
        }

        return $query->{$method}(...$arguments);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        $instance = new static();

        return $instance->{$method}(...$arguments);
    }

    private function shortName(string $class): string
    {
        return str_contains($class, '\\')
            ? substr($class, (int) strrpos($class, '\\') + 1)
            : $class;
    }
}
