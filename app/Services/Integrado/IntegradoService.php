<?php

namespace App\Services\Integrado;

use App\Models;
use App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IntegradoService
{
    private const CACHE_KEY_INTEGRADOS_ALERTA = 'integrados_com_alerta_ids';
    private const CACHE_DURATION = 604800; // 7 dias em segundos

    public function getKmCadastroIntegrado() {}

    public function buscaIntegrado(string $nome): ?Models\Integrado
    {
        if ($nome) {
            $codigoIntegrado = $this->extrairCodigoIntegrado($nome);

            return Models\Integrado::query()->where('codigo', $codigoIntegrado)
                ->first();
        } else {
            Log::alert("10 - Nome do integrado vazio ao buscar integrado.");
        }

        return null;
    }

    public function getIntegradoByCodigo(string $codigo): ?Models\Integrado
    {
        return Models\Integrado::query()->where('codigo', $codigo)->first();
    }

    public function extrairCodigoIntegrado(string $nome): ?string
    {
        if (stripos($nome, 'BRF') !== false) {
            return 0;
        }

        if (preg_match('/\((\d+)/', $nome, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function atualizarKmRota(Models\Integrado $integrado, float $kmRota): void
    {
        $integrado->update(['km_rota' => $kmRota]);
    }

    /**
     * Verifica se um integrado deve receber alerta
     * Usa cache para otimizar consultas repetidas
     * 
     * @param int $id
     * @return bool
     */
    public function getIntegradoAlertaById(int $id): bool
    {
        // Busca a lista de IDs em cache
        $integradosAlerta = Cache::remember(
            self::CACHE_KEY_INTEGRADOS_ALERTA,
            self::CACHE_DURATION,
            function () {
                return Models\Integrado::where('alerta_viagem', true)
                    ->pluck('id')
                    ->toArray();
            }
        );

        return in_array($id, $integradosAlerta, true);
    }

    /**
     * Invalida o cache de integrados com alerta
     * Deve ser chamado quando um integrado for atualizado
     * 
     * @return void
     */
    public static function invalidarCacheIntegradosAlerta(): void
    {
        Cache::forget(self::CACHE_KEY_INTEGRADOS_ALERTA);
        
        Log::info('Cache de integrados com alerta invalidado', [
            'metodo' => __METHOD__ . '@' . __LINE__,
        ]);
    }

    /**
     * Força a atualização do cache de integrados com alerta
     * 
     * @return array
     */
    public function atualizarCacheIntegradosAlerta(): array
    {
        $this->invalidarCacheIntegradosAlerta();
        
        $integradosAlerta = Models\Integrado::where('alerta_viagem', true)
            ->pluck('id')
            ->toArray();
        
        Cache::put(
            self::CACHE_KEY_INTEGRADOS_ALERTA,
            $integradosAlerta,
        );
        
        Log::info('Cache de integrados com alerta atualizado', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'total' => count($integradosAlerta),
        ]);
        
        return $integradosAlerta;
    }

    /**
     * Retorna todos os integrados com alerta (do cache)
     * 
     * @return array
     */
    public function getIntegradosComAlerta(): array
    {
        return Cache::remember(
            self::CACHE_KEY_INTEGRADOS_ALERTA,
            function () {
                return Models\Integrado::where('alerta_viagem', true)
                    ->pluck('id')
                    ->toArray();
            }
        );
    }
}
