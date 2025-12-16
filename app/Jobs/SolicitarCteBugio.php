<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CteService\CteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\NotificacaoService as notify;
use Opcodes\LogViewer\Facades\Cache;

class SolicitarCteBugio implements ShouldQueue
{
    use Queueable;

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
                'metodo' => __METHOD__ . '@' . __LINE__,
                'veiculo' => $this->data['veiculo'] ?? null,
                'attempt' => $this->attempts(),
            ]);

            $lockKey = 'cte:solicitar:bugio';

            $lock = Cache::lock($lockKey, self::LOCK_TTL);

            $lock->block(self::BLOCK);

            try {
                Log::info('Lock adquirido para solicitação de CTe', [
                    'veiculo' => $this->data['veiculo'] ?? null,
                    'attempt' => $this->attempts(),
                ]);

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

                sleep(240); // 4 minutos

            } finally {
                Log::info('Liberando lock para solicitação de CTe', [
                    'veiculo' => $this->data['veiculo'] ?? null,
                    'attempt' => $this->attempts(),
                ]);
                $lock->release();
            }

            Log::info('Solicitação de CTe enviada com sucesso', [
                'veiculo'   => $this->data['veiculo'] ?? null,
                'nro_notas' => $this->data['nro_notas'] ?? null,
                'attempt'   => $this->attempts(),
            ]);

            notify::success('Solicitação de CTe enviada com sucesso!', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $this->data['created_by']);
        } catch (\Exception $e) {
            notify::error('Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $this->data['created_by']);
            Log::error('Erro ao solicitar CTE', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'veiculo' => $this->data['veiculo'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
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
            notify::error('Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $this->data['created_by']);
        }

        // Notificar administradores
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            notify::error('Admin - Erro ao solicitar CTe', "Placa: " . ($this->data['veiculo'] ?? 'N/A') . ' | Notas: ' . ($this->data['nro_notas'] ?? 'N/A' . ' | Integrado: ' . ($this->data['destinos'][0]['integrado_nome'] ?? 'N/A')), true, $admins);
        }
    }
}
