<?php

declare(strict_types=1);

namespace Pxshot;

/**
 * Contains rate limit information from API response headers.
 */
class RateLimitInfo
{
    public function __construct(
        private ?int $limit = null,
        private ?int $remaining = null,
        private ?int $reset = null,
        private ?int $retryAfter = null
    ) {
    }

    /**
     * Create from response headers.
     *
     * @param array<string, array<string>|string> $headers
     */
    public static function fromHeaders(array $headers): self
    {
        $getHeader = function (string $name) use ($headers): ?string {
            $key = strtolower($name);
            foreach ($headers as $headerName => $value) {
                if (strtolower($headerName) === $key) {
                    return is_array($value) ? ($value[0] ?? null) : $value;
                }
            }
            return null;
        };

        $limit = $getHeader('X-RateLimit-Limit');
        $remaining = $getHeader('X-RateLimit-Remaining');
        $reset = $getHeader('X-RateLimit-Reset');
        $retryAfter = $getHeader('Retry-After');

        return new self(
            $limit !== null ? (int) $limit : null,
            $remaining !== null ? (int) $remaining : null,
            $reset !== null ? (int) $reset : null,
            $retryAfter !== null ? (int) $retryAfter : null
        );
    }

    /**
     * Get the maximum number of requests allowed in the current window.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Get the number of requests remaining in the current window.
     */
    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    /**
     * Get the Unix timestamp when the rate limit window resets.
     */
    public function getReset(): ?int
    {
        return $this->reset;
    }

    /**
     * Get the number of seconds to wait before retrying (when rate limited).
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Check if rate limit information is available.
     */
    public function isAvailable(): bool
    {
        return $this->limit !== null || $this->remaining !== null;
    }
}
