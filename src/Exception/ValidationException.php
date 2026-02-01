<?php

declare(strict_types=1);

namespace Pxshot\Exception;

/**
 * Thrown when the API request validation fails.
 */
class ValidationException extends PxshotException
{
    /**
     * @var array<string, array<string>>
     */
    private array $errors;

    /**
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        string $message = '',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
