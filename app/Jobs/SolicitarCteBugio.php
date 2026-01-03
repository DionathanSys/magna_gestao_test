<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CteService\CteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\NotificacaoService as notify;
use Carbon\Carbon;
use Illuminate\Queue\Middleware\RateLimited;
use Opcodes\LogViewer\Facades\Cache;

class SolicitarCteBugio implements ShouldQueue
{
    use Queueable;

    public $tries = 10; // Tentativas ilimitadas

    const LOCK_TTL = 300; // Tempo em segundos para o lock
    const BLOCK = 240; // Tempo em segundos para o lock

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            Log::info('Iniciando job de solicitação de CTe', [
                'metodo'    => __METHOD__ . '@' . __LINE__,
                'veiculo'   => $this->data['veiculo'] ?? null,
                'nro_notas' => $this->data['nro_notas'] ?? null,
                'attempt'   => $this->attempts(),
            ]);

            $cacheKeyLastMail = 'cte:last_email_sent_at';
            $cacheKeyNext = 'cte:next_allowed_send_at';

            $minInterval = 240; // 4 minutos em segundos

            $lastSentAt = Cache::get($cacheKeyLastMail);
            $nextRunAt = Cache::get($cacheKeyNext);

            if ($lastSentAt instanceof Carbon) {

                $secondsSinceLastSend = $lastSentAt->diffInSeconds(now());

                Log::debug('Verificando intervalo desde o último envio de CTe notas - ' . implode(', ', $this->data['nro_notas'] ?? []), [
                    'seconds_since_last_send' => $secondsSinceLastSend,
                    'min_interval'            => $minInterval,
                    'last_sent_at'            => $lastSentAt->toDateTimeString(),
                    'now'                     => now()->toDateTimeString(),
                    'teste'                   => $secondsSinceLastSend < $minInterval,
                    'delay'                   => $minInterval - $secondsSinceLastSend,
                    'attempt'                 => $this->attempts(),
                ]);

                if ($secondsSinceLastSend < $minInterval) {
                    $delay = $minInterval - $secondsSinceLastSend;

                    Log::info('Aguardando intervalo mínimo para novo envio de CTe', [
                        'faltam_segundos' => $delay,
                        'ultimo_envio'    => $lastSentAt->toDateTimeString(),
                        'attempt'         => $this->attempts(),
                    ]);

                    if ($nextRunAt instanceof Carbon) {

                        Log::info('Próximo envio agendado em: ' . $nextRunAt->toDateTimeString(), [
                            'metodo'    => __METHOD__ . '@' . __LINE__,
                            'attempt'   => $this->attempts(),
                        ]);

                        $diffToNextRun = now()->diffInSeconds($nextRunAt);

                        if ($diffToNextRun > 0) {
                            $delay = $diffToNextRun + 255; // adiciona mais 4 minutos
                            Log::info('Ajustando delay para o próximo envio permitido', [
                                'metodo'    => __METHOD__ . '@' . __LINE__,
                                'attempt'   => $this->attempts(),
                                'diff_to_next_run' => $diffToNextRun,
                                'new_delay'     => $delay,
                            ]);
                        } else {
                            Log::info('Próximo envio já permitido, mantendo delay calculado', [
                                'metodo'    => __METHOD__ . '@' . __LINE__,
                                'attempt'   => $this->attempts(),
                                'diff_to_next_run' => $diffToNextRun,
                                'delay'     => $delay,
                            ]);
                        }
                    }

                    $newNextRunAt = now()->addSeconds($delay);
                    $this->release($delay);

                    Log::info('Job de solicitação de CTe re-liberado para execução futura', [
                        'metodo'        => __METHOD__ . '@' . __LINE__,
                        'delay_seconds' => $delay,
                        'new_next_run_at' => $newNextRunAt->toDateTimeString(),
                        'attempt'       => $this->attempts(),
                    ]);

                    Cache::put($cacheKeyNext, $newNextRunAt, 3600); // mantém por 1 hora

                    return;
                }
            }

            $service = new CteService();
            $service->solicitarCtePorEmail($this->data);

            if ($service->hasError()) {
                Log::error('Erro ao solicitar CTe via serviço', [
                    'metodo'    => __METHOD__ . '@' . __LINE__,
                    'veiculo'   => $this->data['veiculo'] ?? null,
                    'nro_notas' => $this->data['nro_notas'] ?? null,
                    'errors'    => $service->getErrors(),
                    'attempt'   => $this->attempts(),
                ]);
                throw new \Exception(implode('; ', $service->getErrors()));
            }

            Cache::put($cacheKeyLastMail, now(), 3600); // mantém por 1 hora

            Log::info('Solicitação de CTe enviada com sucesso', [
                'veiculo'   => $this->data['veiculo'] ?? null,
                'nro_notas' => $this->data['nro_notas'] ?? null,
                'attempt'   => $this->attempts(),
            ]);

            notify::success(
                'Solicitação de CTe enviada com sucesso!',
                "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . (implode(', ', $this->data['nro_notas'] ?? []) . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')),
                true,
                $this->data['created_by']
            );
        } catch (\Throwable $e) {
            // notify::error('Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . (implode(', ', $this->data['nro_notas'] ?? []) . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $this->data['created_by']);
            Log::error('Erro ao solicitar CTE', [
                'metodo'  => __METHOD__ . '@' . __LINE__,
                'attempt' => $this->attempts(),
                'tipo'    => get_class($e),
                'error'   => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha'   => $e->getLine(),
                'veiculo' => $this->data['veiculo'] ?? null,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Chamado quando o job falha definitivamente após todas as tentativas
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de solicitação de CTe falhou após todas as tentativas', [
            'metodo'    => __METHOD__ . '@' . __LINE__,
            'attempt' => $this->attempts(),
            'error' => $exception->getMessage(),
            'data' => $this->data,
        ]);

        // Notificar o usuário que criou a solicitação
        if (isset($this->data['created_by'])) {
            notify::error('Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . (implode(', ', $this->data['nro_notas'] ?? []) . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $this->data['created_by']);
        }

        // Notificar administradores
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            notify::error('Admin - Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . (implode(', ', $this->data['nro_notas'] ?? []) . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $admin);
        }
    }
}
