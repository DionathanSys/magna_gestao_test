<?php

namespace App\Services\WebScraper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebScraperViagemAtualCache
{
    private const INDEX_KEY = 'webscraper:viagens_atuais:index';

    private const ITEM_PREFIX = 'webscraper:viagens_atuais:item:';

    private const LOCK_KEY = 'webscraper:viagens_atuais:lock';

    public function put(string $veiculoKey, array $data): void
    {
        $this->withLock(function () use ($veiculoKey, $data): void {
            $index = Cache::get(self::INDEX_KEY, []);
            $index[$veiculoKey] = $veiculoKey;

            $expiresAt = now()->addMinutes((int) config('services.webscraper.viagem_atual_cache_ttl_minutes', 720));

            Cache::put($this->itemKey($veiculoKey), $data, $expiresAt);
            Cache::put(self::INDEX_KEY, $index, $expiresAt);
        });
    }

    public function all(): array
    {
        $index = Cache::get(self::INDEX_KEY, []);

        if (! is_array($index) || $index === []) {
            return [];
        }

        return collect($index)
            ->values()
            ->map(fn (string $veiculoKey): mixed => Cache::get($this->itemKey($veiculoKey)))
            ->filter()
            ->values()
            ->all();
    }

    public function get(string $veiculoKey): ?array
    {
        $data = Cache::get($this->itemKey($veiculoKey));

        return is_array($data) ? $data : null;
    }

    private function itemKey(string $veiculoKey): string
    {
        return self::ITEM_PREFIX.$veiculoKey;
    }

    private function withLock(callable $callback): mixed
    {
        try {
            return Cache::lock(self::LOCK_KEY, 10)->block(5, $callback);
        } catch (\Throwable $exception) {
            Log::error('Falha ao acessar cache de viagem atual WebScraper com lock', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }
}
