<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebScraperSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.webscraper.secret');
        $timestamp = (string) $request->header('X-Webhook-Timestamp', '');
        $signature = (string) $request->header('X-Webhook-Signature', '');
        $requestId = (string) $request->header('X-Request-Id', '');

        if ($secret === '') {
            Log::critical('Secret da API WebScraper nao configurado', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId ?: null,
            ]);

            return response()->json(['message' => 'Integration not configured.'], 503);
        }

        if ($timestamp === '' || $signature === '') {
            Log::warning('Requisicao WebScraper sem assinatura obrigatoria', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId ?: null,
                'ip' => $request->ip(),
                'has_timestamp' => $timestamp !== '',
                'has_signature' => $signature !== '',
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        if (! ctype_digit($timestamp)) {
            Log::warning('Timestamp invalido na requisicao WebScraper', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId ?: null,
                'timestamp' => $timestamp,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $tolerance = (int) config('services.webscraper.signature_tolerance_seconds', 300);
        if (abs(time() - (int) $timestamp) > $tolerance) {
            Log::warning('Timestamp expirado na requisicao WebScraper', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId ?: null,
                'timestamp' => $timestamp,
                'tolerance_seconds' => $tolerance,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Assinatura invalida na requisicao WebScraper', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $requestId ?: null,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}
