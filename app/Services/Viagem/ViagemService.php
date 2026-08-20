<?php

namespace App\Services\Viagem;

use App\Jobs\VincularRegistroResultadoJob;
use App\Models;
use App\Services;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class ViagemService
{
    use ServiceResponseTrait;

    private Services\Veiculo\VeiculoService $veiculoService;

    private Services\Carga\CargaService $cargaService;

    public function __construct()
    {
        $this->veiculoService = new Services\Veiculo\VeiculoService;
        $this->cargaService = new Services\Carga\CargaService;
    }

    public function create(array $data): ?Models\Viagem
    {
        try {

            Log::debug(__METHOD__, [
                'data' => $data,
            ]);

            $action = new Actions\CriarViagem;
            $viagem = $action->handle($data);

            if ($action->hasError) {
                $this->setError('Erro no processo de criação da viagem', $action->errors);

                return null;
            }

            if ($viagem) {
                $this->setSuccess('Viagem criada com sucesso!');
                VincularRegistroResultadoJob::dispatch($viagem->id, Models\Viagem::class);
            }

            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            $this->setError($e->getMessage());

            return null;
        }
    }

    public function update(Models\Viagem $viagem, array $data): ?Models\Viagem
    {
        try {

            $action = new Actions\AtualizarViagem($viagem);
            $viagem = $action->handle($data);
            $this->setSuccess('Viagem atualizada com sucesso!');

            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'viagem_id' => $viagem->id,
                'viagem_numero' => $viagem->numero_viagem,
                'data' => $data,
            ]);
            $this->setError($e->getMessage());

            return null;
        }
    }

    public function updateOrCreate(array $data): ?Models\Viagem
    {
        try {

            $viagem = Models\Viagem::where('numero_viagem', $data['numero_viagem'])->first();
            $action = null;

            switch (true) {
                case $viagem && $viagem->conferido == false:
                    $viagemIdOriginal = $viagem->id;
                    $action = new Actions\AtualizarViagem($viagem);
                    $viagem = $action->handle($data);
                    VincularRegistroResultadoJob::dispatch($viagem->id, Models\Viagem::class);
                    Log::info('Viagem atualizada por integracao', [
                        'metodo' => __METHOD__.'@'.__LINE__,
                        'viagem_id' => $viagem->id,
                        'numero_viagem' => $viagem->numero_viagem,
                        'conferido' => $viagem->conferido,
                    ]);
                    $this->setSuccess('Viagem atualizada com sucesso!', [
                        'acao' => 'atualizada',
                        'viagem_id' => $viagemIdOriginal,
                    ]);
                    break;

                case $viagem && $viagem->conferido == true:
                    Log::info('Viagem recebida por integracao ignorada por ja estar conferida', [
                        'metodo' => __METHOD__.'@'.__LINE__,
                        'viagem_id' => $viagem->id,
                        'numero_viagem' => $viagem->numero_viagem,
                        'conferido' => $viagem->conferido,
                    ]);
                    $this->setInfo('Viagem Nº '.$viagem['numero_viagem'].' já conferida, não será atualizado', [
                        'acao' => 'ignorada_conferida',
                        'viagem_id' => $viagem->id,
                    ]);
                    break;

                default:
                    $action = new Actions\CriarViagem;
                    $viagem = $action->handle($data);
                    Log::info('Viagem criada por integracao', [
                        'metodo' => __METHOD__.'@'.__LINE__,
                        'viagem_id' => $viagem?->id,
                        'numero_viagem' => $data['numero_viagem'] ?? null,
                    ]);
                    if ($viagem) {
                        $this->setSuccess('Viagem criada com sucesso!', [
                            'acao' => 'criada',
                            'viagem_id' => $viagem->id,
                        ]);
                        VincularRegistroResultadoJob::dispatch($viagem->id, Models\Viagem::class);
                    }
            }

            if ($action instanceof Actions\CriarViagem && $action->hasError) {
                $this->setError('Erro no processo de criação da viagem', $action->errors);

                return null;
            }

            if (! $viagem) {
                $this->setError('Viagem nao foi criada ou atualizada.');

                return null;
            }

            return $viagem;
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar ou criar viagem ', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $e->getMessage(),
                'viagem_numero' => $data['numero_viagem'] ?? null,
                'data' => $data,
            ]);
            $this->setError($e->getMessage());

            return null;
        }
    }

    public function marcarViagemComoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemConferida)->handle($viagem);
            $this->setSuccess('Viagem conferida com sucesso!');

            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $viagem->toArray(),
            ]);
            $this->setError($e->getMessage());

            return null;
        }
    }

    public function marcarViagemComoNãoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemNãoConferida)->handle($viagem);
            $this->setSuccess('Viagem marcada como não conferida!');

            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'metodo' => __METHOD__.'@'.__LINE__,
                'error' => $e->getMessage(),
                'data' => $viagem->toArray(),
            ]);
            $this->setError($e->getMessage());

            return null;
        }
    }

    public function getKmCadastroIntegrado() {}
}
