<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyAutomationWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = trim((string) $request->header('X-Client-ID', ''));
        $timestamp = trim((string) $request->header('X-Timestamp', ''));
        $nonce = trim((string) $request->header('X-Nonce', ''));
        $signature = trim((string) $request->header('X-Signature', ''));
        $version = trim((string) $request->header('X-Signature-Version', ''));
        $requestId = trim((string) $request->header('X-Request-ID', ''));

        if ($clientId !== (string) config('automation.webhook.client_id')) {
            return $this->reject('CLIENT_FORBIDDEN', 'Emissor de webhook nao autorizado.', 403, $requestId);
        }

        if ($timestamp === '' || ! ctype_digit($timestamp) || $nonce === '' || $signature === '' || $version !== (string) config('automation.signature.version', 'v1')) {
            return $this->reject('INVALID_SIGNATURE', 'Assinatura ausente ou invalida.', 401, $requestId);
        }

        $secrets = array_values(array_filter([
            config('automation.webhook.secret'),
            config('automation.webhook.previous_secret'),
        ]));

        if ($secrets === []) {
            return $this->reject('DEPENDENCY_UNAVAILABLE', 'Segredo de webhook nao configurado.', 503, $requestId);
        }

        $tolerance = (int) config('automation.signature.timestamp_tolerance_seconds', 300);

        if (abs(time() - (int) $timestamp) > $tolerance) {
            return $this->reject('REPLAY_DETECTED', 'Timestamp fora da janela permitida.', 401, $requestId);
        }

        $rawBody = $request->getContent();
        $canonical = implode("\n", [
            strtoupper($request->method()),
            $request->getRequestUri(),
            $timestamp,
            $nonce,
            hash('sha256', $rawBody),
        ]);

        $validSignature = false;

        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $canonical, (string) $secret);
            $validSignature = hash_equals($expected, $signature) || $validSignature;
        }

        if (! $validSignature) {
            Log::warning('Assinatura de webhook da Automation API invalida', [
                'request_id' => $requestId ?: null,
                'client_id' => $clientId,
                'path' => $request->getRequestUri(),
            ]);

            return $this->reject('INVALID_SIGNATURE', 'Assinatura invalida.', 401, $requestId);
        }

        try {
            $nonceKey = 'automation:webhook:nonce:'.$clientId.':'.$nonce;
            $stored = Cache::store((string) config('automation.signature.nonce_cache_store', 'redis'))
                ->add($nonceKey, true, (int) config('automation.signature.nonce_ttl_seconds', 600));
        } catch (Throwable $exception) {
            Log::error('Nao foi possivel validar replay do webhook da Automation API', [
                'request_id' => $requestId ?: null,
                'client_id' => $clientId,
                'error' => $exception->getMessage(),
            ]);

            return $this->reject('DEPENDENCY_UNAVAILABLE', 'Nao foi possivel validar a requisicao.', 503, $requestId);
        }

        if (! $stored) {
            return $this->reject('REPLAY_DETECTED', 'Nonce ja utilizado.', 401, $requestId);
        }

        return $next($request);
    }

    private function reject(string $code, string $message, int $status, string $requestId): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => $requestId ?: null,
            ],
        ], $status);
    }
}
