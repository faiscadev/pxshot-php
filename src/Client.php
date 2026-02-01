<?php

declare(strict_types=1);

namespace Pxshot;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Pxshot\Exception\ApiException;
use Pxshot\Exception\AuthenticationException;
use Pxshot\Exception\PxshotException;
use Pxshot\Exception\RateLimitException;
use Pxshot\Exception\ValidationException;

/**
 * Pxshot API Client.
 *
 * @example
 * ```php
 * $client = new Pxshot\Client('px_your_api_key');
 *
 * // Get screenshot as bytes
 * $image = $client->screenshot(['url' => 'https://example.com']);
 * file_put_contents('screenshot.png', $image);
 *
 * // Get screenshot as hosted URL
 * $result = $client->screenshot(['url' => 'https://example.com', 'store' => true]);
 * echo $result->url;
 * ```
 */
class Client
{
    private const BASE_URL = 'https://api.pxshot.com';
    private const VERSION = '1.0.0';

    private GuzzleClient $httpClient;
    private string $apiKey;
    private string $baseUrl;

    /**
     * @param string $apiKey Your Pxshot API key
     * @param array<string, mixed> $options Additional options:
     *   - base_url: Override the API base URL
     *   - timeout: Request timeout in seconds (default: 60)
     *   - http_client: Custom Guzzle client instance
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($options['base_url'] ?? self::BASE_URL, '/');

        if (isset($options['http_client']) && $options['http_client'] instanceof GuzzleClient) {
            $this->httpClient = $options['http_client'];
        } else {
            $this->httpClient = new GuzzleClient([
                'base_uri' => $this->baseUrl,
                'timeout' => $options['timeout'] ?? 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'User-Agent' => 'pxshot-php/' . self::VERSION,
                    'Accept' => 'application/json',
                ],
            ]);
        }
    }

    /**
     * Capture a screenshot of a URL.
     *
     * @param array<string, mixed> $params Screenshot parameters:
     *   - url (required): The URL to capture
     *   - format: Image format ('png', 'jpeg', 'webp')
     *   - quality: JPEG/WebP quality (1-100)
     *   - width: Viewport width in pixels
     *   - height: Viewport height in pixels
     *   - full_page: Capture full scrollable page
     *   - wait_until: Wait condition ('load', 'domcontentloaded', 'networkidle')
     *   - wait_for_selector: CSS selector to wait for
     *   - wait_for_timeout: Additional wait time in ms
     *   - device_scale_factor: Device scale factor (1-3)
     *   - store: If true, stores image and returns URL; if false, returns bytes
     *   - block_ads: If true, blocks ads and trackers
     *
     * @return string|ScreenshotResponse Image bytes (store=false) or ScreenshotResponse (store=true)
     *
     * @throws ValidationException When parameters are invalid
     * @throws AuthenticationException When API key is invalid
     * @throws RateLimitException When rate limit is exceeded
     * @throws ApiException When API returns an error
     * @throws PxshotException For other errors
     */
    public function screenshot(array $params): string|ScreenshotResponse
    {
        if (empty($params['url'])) {
            throw new ValidationException('URL is required', ['url' => ['The url field is required']]);
        }

        $store = $params['store'] ?? false;

        // Build request body with snake_case keys
        $body = $this->buildScreenshotBody($params);

        try {
            $response = $this->httpClient->post('/v1/screenshot', [
                RequestOptions::JSON => $body,
            ]);

            $rateLimitInfo = RateLimitInfo::fromHeaders($response->getHeaders());

            if ($store) {
                $data = json_decode($response->getBody()->getContents(), true);
                return ScreenshotResponse::fromArray($data, $rateLimitInfo);
            }

            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            throw $this->handleClientException($e);
        } catch (GuzzleException $e) {
            throw new PxshotException('Request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get usage statistics.
     *
     * @return UsageResponse
     *
     * @throws AuthenticationException When API key is invalid
     * @throws RateLimitException When rate limit is exceeded
     * @throws ApiException When API returns an error
     * @throws PxshotException For other errors
     */
    public function usage(): UsageResponse
    {
        try {
            $response = $this->httpClient->get('/v1/usage');

            $rateLimitInfo = RateLimitInfo::fromHeaders($response->getHeaders());
            $data = json_decode($response->getBody()->getContents(), true);

            return UsageResponse::fromArray($data, $rateLimitInfo);
        } catch (ClientException $e) {
            throw $this->handleClientException($e);
        } catch (GuzzleException $e) {
            throw new PxshotException('Request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the SDK version.
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Build the screenshot request body.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function buildScreenshotBody(array $params): array
    {
        $body = ['url' => $params['url']];

        $allowedParams = [
            'format',
            'quality',
            'width',
            'height',
            'full_page',
            'wait_until',
            'wait_for_selector',
            'wait_for_timeout',
            'device_scale_factor',
            'store',
            'block_ads',
        ];

        foreach ($allowedParams as $param) {
            if (isset($params[$param])) {
                $body[$param] = $params[$param];
            }
        }

        return $body;
    }

    /**
     * Handle Guzzle client exceptions.
     */
    private function handleClientException(ClientException $e): PxshotException
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $rateLimitInfo = RateLimitInfo::fromHeaders($response->getHeaders());

        $body = [];
        try {
            $body = json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable) {
            // Ignore JSON decode errors
        }

        $message = $body['message'] ?? $body['error'] ?? $e->getMessage();

        return match ($statusCode) {
            401 => new AuthenticationException($message, $statusCode, $e, $rateLimitInfo),
            422 => new ValidationException(
                $message,
                $body['errors'] ?? [],
                $statusCode,
                $e
            ),
            429 => new RateLimitException($message, $statusCode, $e, $rateLimitInfo),
            default => new ApiException($message, $statusCode, $e, $rateLimitInfo),
        };
    }
}
