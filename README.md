# Pxshot PHP SDK

Official PHP SDK for [Pxshot](https://pxshot.com) - the Screenshot API.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Installation

```bash
composer require pxshot/pxshot
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

$client = new Pxshot\Client('px_your_api_key');

// Capture a screenshot and get the image bytes
$imageBytes = $client->screenshot(['url' => 'https://example.com']);
file_put_contents('screenshot.png', $imageBytes);

// Capture and store, get a hosted URL
$result = $client->screenshot([
    'url' => 'https://example.com',
    'store' => true,
]);
echo $result->url;          // https://cdn.pxshot.com/...
echo $result->expiresAt;    // 2024-01-15T12:00:00Z
```

## Screenshot Options

```php
$result = $client->screenshot([
    // Required
    'url' => 'https://example.com',
    
    // Image options
    'format' => 'png',              // 'png', 'jpeg', 'webp'
    'quality' => 80,                // 1-100 (jpeg/webp only)
    
    // Viewport options
    'width' => 1920,                // Viewport width in pixels
    'height' => 1080,               // Viewport height in pixels
    'device_scale_factor' => 2,     // Device scale (1-3, for retina)
    'full_page' => true,            // Capture full scrollable page
    
    // Wait options
    'wait_until' => 'networkidle',  // 'load', 'domcontentloaded', 'networkidle'
    'wait_for_selector' => '#app',  // Wait for CSS selector
    'wait_for_timeout' => 1000,     // Additional wait in ms
    
    // Performance
    'block_ads' => true,            // Block ads and trackers
    
    // Storage
    'store' => true,                // Store and return URL instead of bytes
]);
```

## Usage Statistics

```php
$usage = $client->usage();

// Access usage data
$data = $usage->getData();
$screenshots = $usage->getScreenshotsCount();
$bytes = $usage->getBytesUsed();

// Check rate limits
$rateLimit = $usage->rateLimitInfo;
echo "Remaining: {$rateLimit->getRemaining()}/{$rateLimit->getLimit()}";
```

## Error Handling

```php
use Pxshot\Client;
use Pxshot\Exception\AuthenticationException;
use Pxshot\Exception\RateLimitException;
use Pxshot\Exception\ValidationException;
use Pxshot\Exception\ApiException;
use Pxshot\Exception\PxshotException;

try {
    $result = $client->screenshot(['url' => 'https://example.com']);
} catch (AuthenticationException $e) {
    // Invalid API key (401)
    echo "Auth failed: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Rate limit exceeded (429)
    $retryAfter = $e->getRetryAfter();
    echo "Rate limited. Retry after {$retryAfter} seconds";
} catch (ValidationException $e) {
    // Invalid parameters (422)
    $errors = $e->getErrors();
    print_r($errors);
} catch (ApiException $e) {
    // Other API errors
    echo "API error ({$e->getCode()}): " . $e->getMessage();
} catch (PxshotException $e) {
    // Network/other errors
    echo "Error: " . $e->getMessage();
}
```

## Rate Limit Information

Rate limit info is available on all responses and exceptions:

```php
$result = $client->screenshot(['url' => 'https://example.com', 'store' => true]);

$rateLimit = $result->rateLimitInfo;
$rateLimit->getLimit();      // Max requests per window
$rateLimit->getRemaining();  // Requests remaining
$rateLimit->getReset();      // Unix timestamp when window resets
```

## Configuration

```php
$client = new Pxshot\Client('px_your_api_key', [
    // Custom base URL (for testing or self-hosted)
    'base_url' => 'https://custom.api.com',
    
    // Request timeout in seconds
    'timeout' => 120,
    
    // Custom Guzzle client
    'http_client' => $yourGuzzleClient,
]);
```

## Response Objects

### ScreenshotResponse (when `store=true`)

```php
$response->url;         // string - CDN URL of the screenshot
$response->expiresAt;   // string - ISO 8601 expiration timestamp
$response->width;       // int - Image width in pixels
$response->height;      // int - Image height in pixels
$response->sizeBytes;   // int - File size in bytes
$response->rateLimitInfo; // RateLimitInfo

// Helper methods
$response->getExpiresAtDateTime(); // DateTimeImmutable
$response->isExpired();            // bool
```

### UsageResponse

```php
$response->getData();            // array - Raw usage data
$response->get('key', $default); // mixed - Get specific value
$response->getScreenshotsCount(); // ?int
$response->getBytesUsed();        // ?int
$response->rateLimitInfo;         // RateLimitInfo
```

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP 7.0 or higher

## License

MIT License. See [LICENSE](LICENSE) for details.
