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
    private const CACHE_DURATION = 300; // 5 minutos

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Busca IDs das cargas pendentes de alerta
        $cargaIds = Cache::pull(self::CACHE_KEY, []);

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

            if ($cargas->isEmpty()) {
                Log::info('Nenhuma carga com integrado de alerta encontrada', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'carga_ids' => $cargaIds,
                ]);
                return;
            }

            //TODO utilizar o cliente de uma das cargas para definir destinatários dinamicamente
            

            // Envia email
            $destinatarios = ['dionathan.silva@transmagnabosco.com.br', 'angelica.perdesseti@transmagnabosco.com.br'];

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
     */
    public static function adicionarCarga(int $cargaId): void
    {
        try {
            $cargaIds = Cache::get(self::CACHE_KEY, []);

            Log::debug('Adicionando carga para alerta de integrados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'carga_id' => $cargaId,
                'cargas_atuais_no_cache' => $cargaIds,
            ]);

            $cargaIds[] = $cargaId;

            Cache::put(self::CACHE_KEY, array_unique($cargaIds), self::CACHE_DURATION);

            // Agenda o job para rodar após o tempo de cache
            self::dispatch()->delay(now()->addSeconds(self::CACHE_DURATION + 5));
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar carga para alerta de integrados', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'carga_id' => $cargaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
