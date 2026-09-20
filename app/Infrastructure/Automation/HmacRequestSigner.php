<?php

namespace App\Infrastructure\Automation;

use App\Domain\Automation\Exceptions\AutomationApiException;
use Illuminate\Support\Str;

class HmacRequestSigner
{
    /**
     * @return array<string, string>
     */
    public function headers(
        string $method,
        string $pathWithQuery,
        string $rawBody,
        ?string $requestId = null,
        ?int $timestamp = null,
        ?string $nonce = null,
    ): array {
        $secret = (string) config('automation.client.secret');

        if ($secret === '') {
            throw new AutomationApiException('Segredo da Automation API nao configurado.');
        }

        $timestamp ??= time();
        $nonce ??= (string) Str::ulid();
        $requestId ??= (string) Str::ulid();
        $bodyHash = hash('sha256', $rawBody);
        $canonical = implode("\n", [
            strtoupper($method),
            $pathWithQuery,
            (string) $timestamp,
            $nonce,
            $bodyHash,
        ]);

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Client-ID' => (string) config('automation.client.id'),
            'X-Timestamp' => (string) $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => hash_hmac('sha256', $canonical, $secret),
            'X-Signature-Version' => (string) config('automation.signature.version', 'v1'),
            'X-Request-ID' => $requestId,
        ];
    }
}
