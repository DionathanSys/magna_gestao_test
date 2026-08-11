<?php

namespace App\Services\WebScraper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SascarMovimentoDiarioCache
{
    private const INDEX_KEY = 'webscraper:sascar_movimento_diario:index';

    private const ITEM_PREFIX = 'webscraper:sascar_movimento_diario:item:';

    private const LOCK_KEY = 'webscraper:sascar_movimento_diario:lock';

    public function put(string $veiculoKey, string $dia, array $data): void
    {
        $cacheKey = $this->cacheKey($veiculoKey, $dia);

        $this->withLock(function () use ($cacheKey, $data): void {
            $index = Cache::get(self::INDEX_KEY, []);
            $index[$cacheKey] = $cacheKey;

            $expiresAt = now()->addMinutes((int) config('services.webscraper.movimento_diario_cache_ttl_minutes', 1440));

            Cache::put($this->itemKey($cacheKey), $data, $expiresAt);
            Cache::put(self::INDEX_KEY, $index, $expiresAt);
        });
    }

    public function get(string $veiculoKey, string $dia): ?array
    {
        $data = Cache::get($this->itemKey($this->cacheKey($veiculoKey, $dia)));

        return is_array($data) ? $data : null;
    }

    public function all(): array
    {
        $index = Cache::get(self::INDEX_KEY, []);

        if (! is_array($index) || $index === []) {
            return [];
        }

        return collect($index)
            ->values()
            ->map(fn (string $cacheKey): mixed => Cache::get($this->itemKey($cacheKey)))
            ->filter()
            ->values()
            ->all();
    }

    private function cacheKey(string $veiculoKey, string $dia): string
    {
        return $veiculoKey.':dia:'.$dia;
    }

    private function itemKey(string $cacheKey): string
    {
        return self::ITEM_PREFIX.$cacheKey;
    }

    private function withLock(callable $callback): mixed
    {
        try {
            return Cache::lock(self::LOCK_KEY, 10)->block(5, $callback);
        } catch (\Throwable $exception) {
            Log::error('Falha ao acessar cache de movimento diario Sascar com lock', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }
}
