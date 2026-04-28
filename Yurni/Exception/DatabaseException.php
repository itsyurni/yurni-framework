<?php

declare(strict_types=1);

namespace yurni\Exception;

/**
 * استثناء فشل الاتصال بقاعدة البيانات.
 */
class DatabaseException extends \RuntimeException
{
    protected $code = 500;
}
