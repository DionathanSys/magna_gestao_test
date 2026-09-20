<?php

namespace App\Domain\Automation\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class AutomationApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $errorCode = null,
        public readonly bool $retryable = false,
        public readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $error = $response->json('error', []);
        $statusCode = $response->status();

        return new self(
            message: (string) ($error['message'] ?? 'Falha na Automation API.'),
            statusCode: $statusCode,
            errorCode: isset($error['code']) ? (string) $error['code'] : null,
            retryable: $statusCode === 408 || $statusCode === 425 || $statusCode === 429 || $statusCode >= 500,
            requestId: isset($error['request_id']) ? (string) $error['request_id'] : null,
        );
    }
}
