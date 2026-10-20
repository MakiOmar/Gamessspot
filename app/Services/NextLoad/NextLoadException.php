<?php

namespace App\Services\NextLoad;

use RuntimeException;

/**
 * Supplier failure with a machine-readable reason so callers can decide to retry, recover or stop.
 */
class NextLoadException extends RuntimeException
{
    public const CONFIG = 'config';

    public const INSUFFICIENT_BALANCE = 'insufficient_balance';

    public const DUPLICATE_REFERENCE = 'duplicate_reference';

    public const FAILED = 'failed';

    public const TRANSPORT = 'transport';

    public function __construct(public readonly string $reason, string $message, public readonly ?int $httpStatus = null)
    {
        parent::__construct($message);
    }

    /** Safe to retry the same request with the same reference. */
    public function isRetryable(): bool
    {
        return $this->reason === self::TRANSPORT;
    }
}
