<?php

declare(strict_types=1);

namespace Pxshot\Exception;

/**
 * Thrown when the API rate limit is exceeded.
 */
class RateLimitException extends PxshotException
{
    /**
     * Get the number of seconds to wait before retrying.
     */
    public function getRetryAfter(): ?int
    {
        return $this->rateLimitInfo?->getRetryAfter();
    }
}
