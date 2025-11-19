<?php

namespace App\Jobs;

use App\Mail\AlertaIntegradosViagem;
use App\Models\CargaViagem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessarAlertasIntegrados implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_KEY = 'alertas_integrados_pendentes';
    private const CACHE_LOCK_KEY = 'alertas_integrados_lock';
    private const DELAY_SECONDS = 180; // 3 minutos

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Job ProcessarAlertasIntegrados iniciado', [
            'metodo' => __METHOD__ . '@' . __LINE__,
        ]);

        $cacheAntesPull = Cache::get(self::CACHE_KEY, []);

        Log::info('Estado do cache ANTES do pull', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'cache_conteudo' => $cacheAntesPull,
            'cache_vazio' => empty($cacheAntesPull),
            'total_itens' => count($cacheAntesPull),
        ]);

        // Busca IDs das cargas pendentes de alerta
        $cargaIds = Cache::pull(self::CACHE_KEY, []);

        Log::debug('Iniciando processamento de alertas de integrados', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'carga_ids' => $cargaIds,
        ]);

        if (empty($cargaIds)) {
            Log::info('Nenhuma carga pendente de alerta', [
                'metodo' => __METHOD__ . '@' . __LINE__,
            ]);
            return;
        }

        try {
            // Carrega todas as cargas de uma vez com relacionamentos
            $cargas = CargaViagem::with([
                'viagem.veiculo:id,placa',
                'integrado:id,codigo,nome,municipio,cliente,alerta_viagem',
            ])
                ->whereIn('id', $cargaIds)
                ->whereHas('integrado', fn($q) => $q->where('alerta_viagem', true))
                ->get();

            Log::debug('Cargas com integrados de alerta carregadas', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'cargas_encontradas' => $cargas,
            ]);

            if ($cargas->isEmpty()) {
                Log::info('Nenhuma carga com integrado de alerta encontrada', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'carga_ids' => $cargaIds,
                ]);
                return;
            }

            //TODO utilizar o cliente de uma das cargas para definir destinatários dinamicamente

            // Envia email
            $destinatarios = ['dionathan.silva@transmagnabosco.com.br', 'angelica.perdessetti@transmagnabosco.com.br'];

            Mail::to($destinatarios)->send(new AlertaIntegradosViagem($cargas));

            Log::info('Email de alerta de integrados enviado', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'total_cargas' => $cargas->count(),
                'total_integrados' => $cargas->pluck('integrado_id')->unique()->count(),
                'destinatarios' => $destinatarios,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar alertas de integrados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Adiciona uma carga para ser alertada
     * ⭐ LÓGICA CORRIGIDA: Só despacha job UMA VEZ
     */
    public static function adicionarCarga(int $cargaId): void
    {
        try {
            // Evitar race condition
            $lock = Cache::lock(self::CACHE_LOCK_KEY, 10);

            if (!$lock->get()) {
                Log::warning('Não conseguiu adquirir lock para adicionar carga', [
                    'carga_id' => $cargaId,
                ]);
                return;
            }

            try {
                // Busca cargas atuais
                $cargaIds = Cache::get(self::CACHE_KEY, []);
                $eraVazio = empty($cargaIds);

                // Adiciona nova carga
                $cargaIds[] = $cargaId;
                $cargaIds = array_unique($cargaIds);

                // Salva no cache
                Cache::forever(self::CACHE_KEY, $cargaIds);

                $verificacao = Cache::get(self::CACHE_KEY, []);

                Log::info('Carga adicionada ao cache', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'carga_id' => $cargaId,
                    'total_no_cache' => count($cargaIds),
                    'cache_verificado' => $verificacao,
                    'cache_salvou_corretamente' => in_array($cargaId, $verificacao),
                    'era_vazio' => $eraVazio,
                ]);

                // IMPORTANTE: Só despacha job se o cache estava vazio
                if ($eraVazio) {
                    $dataProcessamento = now()->addSeconds(self::DELAY_SECONDS);

                    self::dispatch()->delay($dataProcessamento);

                    Log::info('Job de alertas despachado', [
                        'metodo' => __METHOD__ . '@' . __LINE__,
                        'delay_segundos' => self::DELAY_SECONDS,
                        'processara_em' => $dataProcessamento->format('Y-m-d H:i:s'),
                        'processara_em_timestamp' => $dataProcessamento->timestamp,
                        'agora' => now()->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    Log::debug('Job já foi despachado anteriormente, aguardando processamento');
                }
            } finally {
                $lock->release();
            }
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar carga para alerta', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'carga_id' => $cargaId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job ProcessarAlertasIntegrados falhou', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'error' => $exception->getMessage(),
        ]);
    }
}
