<?php

namespace App\Services\Veiculo;

use App\Models\Veiculo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VeiculoCacheService
{
    private const CACHE_KEY_PLACAS_ATIVAS = 'veiculos_placas_ativas';
    private const CACHE_KEY_PLACAS_TODAS = 'veiculos_placas';
    private const CACHE_TTL = 604800; // 7 dias

    /**
     * Retorna placas de veículos ativos para SelectFilter
     */
    public static function getPlacasAtivasForSelect(): array
    {
        return Cache::remember(self::CACHE_KEY_PLACAS_ATIVAS, self::CACHE_TTL, function () {
            Log::debug('Carregando placas ativas do banco de dados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
            ]);

            return Veiculo::query()
                ->select('id', 'placa')
                ->where('is_active', true)
                ->orderBy('placa')
                ->get()
                ->pluck('placa', 'id')
                ->toArray();
        });
    }

    /**
     * Retorna todas as placas (ativas e inativas) para SelectFilter
     */
    public static function getTodasPlacasForSelect(): array
    {
        return Cache::remember(self::CACHE_KEY_PLACAS_TODAS, self::CACHE_TTL, function () {
            Log::debug('Carregando todas as placas do banco de dados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
            ]);

            return Veiculo::query()
                ->select('id', 'placa', 'is_active')
                ->orderBy('placa')
                ->get()
                ->mapWithKeys(function ($veiculo) {
                    $label = $veiculo->placa . ($veiculo->is_active ? '' : ' (Inativo)');
                    return [$veiculo->id => $label];
                })
                ->toArray();
        });
    }

    /**
     * Retorna informações completas dos veículos com cache
     */
    public static function getVeiculosCompletos(): Collection
    {
        return Cache::remember('veiculos_completos', self::CACHE_TTL, function () {
            Log::debug('Carregando veículos completos do banco de dados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
            ]);

            return Veiculo::query()
                ->select('id', 'placa', 'is_active', 'marca', 'modelo', 'ano')
                ->with(['tipoVeiculo:id,nome'])
                ->orderBy('placa')
                ->get();
        });
    }

    /**
     * Invalida todos os caches relacionados a veículos
     */
    public static function invalidarCacheVeiculos(?int $veiculoId = null): void
    {
        $keys = [
            self::CACHE_KEY_PLACAS_ATIVAS,
            self::CACHE_KEY_PLACAS_TODAS,
            'veiculos_completos',
        ];

        // Se for um veículo específico, limpa também caches individuais
        if ($veiculoId) {
            $keys = array_merge($keys, [
                'km_atual_veiculo_id_' . $veiculoId,
                'km_ultimo_movimento_veiculo_id_' . $veiculoId,
            ]);
        }

        foreach ($keys as $key) {
            Cache::forget($key);
            Log::debug('Cache invalidado', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'cache_key' => $key,
                'veiculo_id' => $veiculoId,
            ]);
        }

        Log::info('Cache de veículos invalidado completamente', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'veiculo_id' => $veiculoId,
            'total_keys' => count($keys),
        ]);
    }

    /**
     * Pré-carrega todos os caches de veículos
     */
    public static function precarregarCaches(): void
    {
        Log::info('Iniciando pré-carregamento de caches de veículos', [
            'metodo' => __METHOD__ . '@' . __LINE__,
        ]);

        self::getPlacasAtivasForSelect();
        self::getTodasPlacasForSelect();
        self::getVeiculosCompletos();

        Log::info('Pré-carregamento de caches de veículos concluído', [
            'metodo' => __METHOD__ . '@' . __LINE__,
        ]);
    }

    /**
     * Retorna estatísticas dos caches
     */
    public static function getEstatisticasCache(): array
    {
        $keys = [
            'placas_ativas' => self::CACHE_KEY_PLACAS_ATIVAS,
            'placas_todas' => self::CACHE_KEY_PLACAS_TODAS,
            'veiculos_completos' => 'veiculos_completos',
        ];

        $stats = [];
        foreach ($keys as $nome => $key) {
            $stats[$nome] = [
                'existe' => Cache::has($key),
                'tamanho' => Cache::has($key) ? count(Cache::get($key, [])) : 0,
            ];
        }

        return $stats;
    }
}