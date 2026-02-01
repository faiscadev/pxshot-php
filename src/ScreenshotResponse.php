<?php

declare(strict_types=1);

namespace Pxshot;

/**
 * Response object for stored screenshots (when store=true).
 */
class ScreenshotResponse
{
    public function __construct(
        public readonly string $url,
        public readonly string $expiresAt,
        public readonly int $width,
        public readonly int $height,
        public readonly int $sizeBytes,
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
        return new self(
            url: $data['url'] ?? '',
            expiresAt: $data['expires_at'] ?? '',
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            sizeBytes: (int) ($data['size_bytes'] ?? 0),
            rateLimitInfo: $rateLimitInfo
        );
    }

    /**
     * Get the expiration time as a DateTime object.
     */
    public function getExpiresAtDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->expiresAt);
    }

    /**
     * Check if the URL has expired.
     */
    public function isExpired(): bool
    {
        return $this->getExpiresAtDateTime() < new \DateTimeImmutable();
    }
}
