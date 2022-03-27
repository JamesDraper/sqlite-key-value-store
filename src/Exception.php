<?php
declare(strict_types=1);

namespace SqliteKeyValueStore;

use Throwable;

/**
 * Exception thrown by any errors from within this package.
 */
final class Exception extends \Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
