<?php

declare(strict_types=1);

namespace Pxshot\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pxshot\Client;
use Pxshot\Exception\AuthenticationException;
use Pxshot\Exception\RateLimitException;
use Pxshot\Exception\ValidationException;
use Pxshot\ScreenshotResponse;
use Pxshot\UsageResponse;

class ClientTest extends TestCase
{
    private function createClientWithMock(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        return new Client('px_test_key', ['http_client' => $httpClient]);
    }

    public function testScreenshotReturnsBytes(): void
    {
        $imageBytes = 'fake-image-bytes';
        $client = $this->createClientWithMock([
            new Response(200, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '99',
            ], $imageBytes),
        ]);

        $result = $client->screenshot(['url' => 'https://example.com']);

        $this->assertEquals($imageBytes, $result);
    }

    public function testScreenshotReturnsResponseWhenStored(): void
    {
        $responseData = [
            'url' => 'https://cdn.pxshot.com/abc123.png',
            'expires_at' => '2025-01-15T12:00:00Z',
            'width' => 1920,
            'height' => 1080,
            'size_bytes' => 123456,
        ];

        $client = $this->createClientWithMock([
            new Response(200, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '99',
            ], json_encode($responseData)),
        ]);

        $result = $client->screenshot(['url' => 'https://example.com', 'store' => true]);

        $this->assertInstanceOf(ScreenshotResponse::class, $result);
        $this->assertEquals('https://cdn.pxshot.com/abc123.png', $result->url);
        $this->assertEquals(1920, $result->width);
        $this->assertEquals(1080, $result->height);
        $this->assertEquals(123456, $result->sizeBytes);
    }

    public function testUsageReturnsResponse(): void
    {
        $usageData = [
            'screenshots_count' => 150,
            'bytes_used' => 5000000,
        ];

        $client = $this->createClientWithMock([
            new Response(200, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '99',
            ], json_encode($usageData)),
        ]);

        $result = $client->usage();

        $this->assertInstanceOf(UsageResponse::class, $result);
        $this->assertEquals(150, $result->getScreenshotsCount());
        $this->assertEquals(5000000, $result->getBytesUsed());
    }

    public function testAuthenticationException(): void
    {
        $client = $this->createClientWithMock([
            new Response(401, [], json_encode(['message' => 'Invalid API key'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->screenshot(['url' => 'https://example.com']);
    }

    public function testRateLimitException(): void
    {
        $client = $this->createClientWithMock([
            new Response(429, [
                'Retry-After' => '60',
            ], json_encode(['message' => 'Rate limit exceeded'])),
        ]);

        try {
            $client->screenshot(['url' => 'https://example.com']);
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(60, $e->getRetryAfter());
        }
    }

    public function testValidationExceptionForMissingUrl(): void
    {
        $client = new Client('px_test_key');

        $this->expectException(ValidationException::class);
        $client->screenshot([]);
    }

    public function testEmptyApiKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('');
    }

    public function testGetVersion(): void
    {
        $this->assertIsString(Client::getVersion());
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Client::getVersion());
    }
}
