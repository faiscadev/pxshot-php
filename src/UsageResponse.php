<?php

declare(strict_types=1);

namespace Pxshot;

/**
 * Response object for usage statistics.
 */
class UsageResponse
{
    /**
     * @param array<string, mixed> $data Raw usage data from API
     */
    public function __construct(
        private array $data,
        public readonly RateLimitInfo $rateLimitInfo
    ) {
    }

    /**
     * Create from API response.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, RateLimitInfo $rateLimitInfo): self
    {
        return new self($data, $rateLimitInfo);
    }

    /**
     * Get the raw usage data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific value from the usage data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get the number of screenshots taken.
     */
    public function getScreenshotsCount(): ?int
    {
        $count = $this->data['screenshots_count'] ?? $this->data['screenshots'] ?? null;
        return $count !== null ? (int) $count : null;
    }

    /**
     * Get the total bytes used.
     */
    public function getBytesUsed(): ?int
    {
        $bytes = $this->data['bytes_used'] ?? $this->data['storage_bytes'] ?? null;
        return $bytes !== null ? (int) $bytes : null;
    }
}
