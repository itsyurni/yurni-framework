<?php

declare(strict_types=1);

namespace yurni;

use PDO;
use PDOStatement;
use PDOException;
use yurni\Database\QueryBuilder;
use yurni\Exception\DatabaseException;
use yurni\Exception\QueryException;

/**
 * ====================================================================
 *  Db — Database Connection Manager
 * ====================================================================
 *
 * Database connection management class.
 * Uses the Singleton pattern to ensure a single PDO connection throughout the request lifecycle.
 *
 * Features:
 *  - Full UTF-8 support (charset + collation)
 *  - MySQL Strict mode enabled
 *  - Optional Query Logging
 *  - Execution time tracking per query
 *  - Transaction helpers (beginTransaction / commit / rollback)
 *  - Fluent Query Builder API via table() and query()
 *  - statement(), select(), and insertGetId() for backward compatibility
 *  - getPdo() for direct access to the underlying PDO instance
 *
 * @package yurni
 */
class Db
{
    // =========================================================================
    //  Singleton & PDO
    // =========================================================================

    /** @var self|null Singleton instance */
    private static ?self $instance = null;

    /** @var PDO|null Lazy-initialized PDO connection */
    private ?PDO $pdo = null;

    // =========================================================================
    //  Query Log
    // =========================================================================

    private bool $loggingEnabled = false;
    private array $queryLog = [];

    // =========================================================================
    //  Constructor (Private — Singleton)
    // =========================================================================

    /**
     * Private constructor — connection is NOT made here.
     * The actual PDO connection is created lazily on the first query.
     */
    private function __construct()
    {
    }

    // =========================================================================
    //  Singleton Access
    // =========================================================================

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    // =========================================================================
    //  PDO — Lazy Connection
    // =========================================================================

    /**
     * Returns the PDO connection, connecting on first call.
     *
     * @throws DatabaseException if connection fails
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Check if a database is configured and reachable without throwing.
     */
    public function isConnected(): bool
    {
        try {
            $this->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function connect(): void
    {
        // Config keys match README documentation:
        // DB_DRIVER / DB_HOST / DB_NAME / DB_USER / DB_PASS
        $driver = strtolower(Config::get('db_driver', 'mysql'));
        $host = Config::get('db_host', '127.0.0.1');
        $port = Config::get('db_port', $driver === 'pgsql' ? '5432' : '3306');
        $dbname = Config::get('db_name', '');
        $user = Config::get('db_user', 'root');
        $pass = Config::get('db_pass', '');
        $charset = Config::get('db_charset', 'utf8mb4');

        [$dsn, $options] = match ($driver) {

            // ── SQLite ──────────────────────────────────────────────────────
            'sqlite' => [
                "sqlite:{$dbname}",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],

            // ── PostgreSQL ──────────────────────────────────────────────────
            'pgsql' => [
                "pgsql:host={$host};port={$port};dbname={$dbname}",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],

            // ── MySQL / MariaDB (Default) ───────────────────────────────────
            default => [
                "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND =>
                        "SET NAMES {$charset} COLLATE "
                        . Config::get('db_collation', 'utf8mb4_unicode_ci')
                        . ", time_zone = '"
                        . Config::get('db_timezone', '+00:00') . "'",
                ],
            ],
        };

        try {
            $this->pdo = new PDO($dsn, $driver === 'sqlite' ? null : $user, $driver === 'sqlite' ? null : $pass, $options);
        } catch (PDOException $e) {
            error_log('[Yurni\Db] Connection failed: ' . $e->getMessage());
            throw new DatabaseException(
                'Database connection failed. Please check your .env configuration.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    public function table(string $table): QueryBuilder
    {
        return $this->query()->table($table);
    }

    // =========================================================================
    //  Statement Execution
    // =========================================================================

    /**
     * Execute a Prepared Statement and return the PDOStatement.
     *
     * @param string $sql      SQL query string
     * @param array  $bindings Bound values
     * @return PDOStatement
     *
     * @throws QueryException
     */
    public function statement(string $sql, array $bindings = []): PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->getPdo()->prepare($sql);
            foreach ($bindings as $key => $value) {
                $param = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? (string) $key : ':' . $key);
                $stmt->bindValue($param, $value, $this->detectPdoParamType($value));
            }

            $stmt->execute();
        } catch (PDOException $e) {
            error_log("[Yurni\\Db] Query failed: {$sql} | " . json_encode($bindings));
            throw new QueryException($sql, $bindings, $e);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $this->logQuery($sql, $bindings, $elapsed);

        return $stmt;
    }

    /**
     * Execute a SELECT query and return all rows.
     *
     * @param string $sql
     * @param array  $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->statement($sql, $bindings)->fetchAll();
    }

    /**
     * Execute a SELECT query and return the first row only.
     *
     * @param string $sql
     * @param array  $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->statement($sql, $bindings)->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute INSERT / UPDATE / DELETE and return the number of affected rows.
     *
     * @param string $sql
     * @param array  $bindings
     * @return int
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        return $this->statement($sql, $bindings)->rowCount();
    }

    /**
     * Execute an INSERT query and return the last inserted ID.
     *
     * @param string $sql
     * @param array  $bindings
     * @return int
     */
    public function insertGetId(string $sql, array $bindings = []): int
    {
        $this->statement($sql, $bindings);
        return (int) $this->getPdo()->lastInsertId();
    }

    /**
     * Execute a query without a Prepared Statement (DDL only).
     * WARNING: Do not use with user-provided input.
     *
     * @param string $sql
     * @return int
     */
    public function unprepared(string $sql): int
    {
        $start = microtime(true);
        try {
            $rows = $this->getPdo()->exec($sql);
        } catch (PDOException $e) {
            error_log("[Yurni\\Db] Unprepared query failed: {$sql}");
            throw new QueryException($sql, [], $e);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $this->logQuery($sql, [], $elapsed);

        return $rows === false ? 0 : $rows;
    }

    // =========================================================================
    //  Transactions
    // =========================================================================

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void
    {
        if (!$this->getPdo()->inTransaction()) {
            $this->getPdo()->beginTransaction();
        }
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        if ($this->getPdo()->inTransaction()) {
            $this->getPdo()->commit();
        }
    }

    /**
     * Rollback the current transaction.
     */
    public function rollback(): void
    {
        if ($this->getPdo()->inTransaction()) {
            $this->getPdo()->rollBack();
        }
    }

    /**
     * Execute a Closure within a transaction with automatic Rollback on error.
     *
     * @param  callable $callback
     * @return mixed Result of the callback
     *
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Is the connection currently in a transaction?
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    // =========================================================================
    //  Query Log
    // =========================================================================

    /**
     * Enable query logging.
     */
    public function enableQueryLog(): void
    {
        $this->loggingEnabled = true;
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLog(): void
    {
        $this->loggingEnabled = false;
    }

    /**
     * Retrieve the executed query log.
     *
     * @return array<int, array{sql: string, bindings: array, time_ms: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Get the total execution time of all logged queries (in ms).
     */
    public function getTotalQueryTime(): float
    {
        return array_sum(array_column($this->queryLog, 'time_ms'));
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    /**
     * Log a query internally if logging is enabled.
     */
    private function logQuery(string $sql, array $bindings, float $timeMs): void
    {
        if ($this->loggingEnabled) {
            $this->queryLog[] = [
                'sql' => $sql,
                'bindings' => $bindings,
                'time_ms' => round($timeMs, 4),
            ];
        }
    }

    private function detectPdoParamType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    // =========================================================================
    //  Magic Methods
    // =========================================================================

    /**
     * Prevent cloning to maintain Singleton pattern.
     */
    private function __clone()
    {
    }

    /**
     * Prevent deserialization.
     */
    public function __wakeup(): void
    {
        throw new \LogicException('Deserialization of Db instance is prohibited.');
    }
}
