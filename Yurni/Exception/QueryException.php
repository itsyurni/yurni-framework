<?php

declare(strict_types=1);

namespace yurni\Exception;

use PDOException;

/**
 * ====================================================================
 *  QueryException — استثناء فشل تنفيذ استعلام SQL
 * ====================================================================
 *
 * يحمل نص الاستعلام والـ bindings المستخدمة لتسهيل تتبع الخطأ.
 *
 * @package yurni\Exception
 */
class QueryException extends \RuntimeException
{
    protected $code = 500;

    /** @var string نص الاستعلام الذي تسبب في الخطأ */
    private string $sql;

    /** @var array القيم المرتبطة بالاستعلام */
    private array $bindings;

    /**
     * @param string       $sql      نص الاستعلام
     * @param array        $bindings القيم المرتبطة
     * @param PDOException $previous الاستثناء الأصلي من PDO
     */
    public function __construct(string $sql, array $bindings, PDOException $previous)
    {
        $message = sprintf(
            "Query failed: %s\nBindings: %s\nPDO: %s",
            $sql,
            json_encode($bindings, JSON_UNESCAPED_UNICODE),
            $previous->getMessage()
        );

        parent::__construct($message, (int) $previous->getCode(), $previous);

        $this->sql      = $sql;
        $this->bindings = $bindings;
    }

    /** إرجاع نص الاستعلام الفاشل. */
    public function getSql(): string
    {
        return $this->sql;
    }

    /** إرجاع القيم المرتبطة. */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
