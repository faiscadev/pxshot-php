<?php

declare(strict_types=1);

namespace Pxshot\Exception;

use Exception;
use Pxshot\RateLimitInfo;

/**
 * Base exception for all Pxshot SDK errors.
 */
class PxshotException extends Exception
{
    protected ?RateLimitInfo $rateLimitInfo = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?RateLimitInfo $rateLimitInfo = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->rateLimitInfo = $rateLimitInfo;
    }

    public function getRateLimitInfo(): ?RateLimitInfo
    {
        return $this->rateLimitInfo;
    }
}
