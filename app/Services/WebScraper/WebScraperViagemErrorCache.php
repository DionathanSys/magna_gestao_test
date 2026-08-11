<?php

namespace App\Services\WebScraper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebScraperViagemErrorCache
{
    private const CACHE_KEY = 'webscraper:viagens:errors';

    private const LOCK_KEY = 'webscraper:viagens:errors:lock';

    public function add(array $error): void
    {
        $this->withLock(function () use ($error): void {
            $errors = Cache::get(self::CACHE_KEY, []);
            $errors[] = [
                ...$error,
                'capturado_em' => now()->toDateTimeString(),
            ];

            Cache::put(
                self::CACHE_KEY,
                $errors,
                now()->addMinutes((int) config('services.webscraper.error_cache_ttl_minutes', 120))
            );
        });
    }

    public function drain(): array
    {
        return $this->withLock(function (): array {
            $errors = Cache::get(self::CACHE_KEY, []);
            Cache::forget(self::CACHE_KEY);

            return is_array($errors) ? $errors : [];
        });
    }

    public function count(): int
    {
        $errors = Cache::get(self::CACHE_KEY, []);

        return is_array($errors) ? count($errors) : 0;
    }

    private function withLock(callable $callback): mixed
    {
        try {
            return Cache::lock(self::LOCK_KEY, 10)->block(5, $callback);
        } catch (\Throwable $exception) {
            Log::error('Falha ao acessar cache de erros WebScraper com lock', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }
}
