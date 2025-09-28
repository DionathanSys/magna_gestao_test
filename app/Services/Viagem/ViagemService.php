<?php

namespace App\Services\Viagem;

use App\DTO\ViagemDTO;
use App\Models;
use App\Services;
use App\Traits\ServiceResponseTrait;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;

class ViagemService
{

    use ServiceResponseTrait;

    private Services\Veiculo\VeiculoService $veiculoService;
    private Services\Carga\CargaService     $cargaService;

    public function __construct()
    {
        $this->veiculoService = new Services\Veiculo\VeiculoService();
        $this->cargaService = new Services\Carga\CargaService();
    }

    public function create(array $data): ?Models\Viagem
    {
        try {

            $action = new Actions\CriarViagem();
            $viagem = $action->handle($data);
            $this->setSuccess('Viagem criada com sucesso!');

            if (isset($data['destino']) && ($data['destino'] instanceof Models\Integrado)) {
                $integrado = $data['destino'];
                $this->cargaService->create($integrado, $viagem);
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
                'error'         => $e->getMessage(),
                'viagem_id'     => $viagem->id,
                'viagem_numero' => $viagem->numero_viagem,
                'data'          => $data,
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function updateOrCreate(array $data): ?Models\Viagem
    {
        try {

            $viagem = Models\Viagem::where('numero_viagem', $data['numero_viagem'])->first();

            Log::debug(__METHOD__ . '@' . __LINE__, [
                'viagem' => $viagem ?? $data['numero_viagem'] . ' (nova)',
            ]);

            switch (true) {
                case ($viagem && $viagem->conferido == false):
                    $action = new Actions\AtualizarViagem($viagem);
                    $viagem = $action->handle($data);
                    Log::info("Viagem Nº " . $viagem['numero_viagem'] . " atualizada");
                    $this->setSuccess('Viagem atualizada com sucesso!');
                    break;

                case ($viagem && $viagem->conferido == true):
                    $this->setSuccess("Viagem Nº " . $viagem['numero_viagem'] . " já conferida, não será atualizado");
                    break;

                default:
                    $action = new Actions\CriarViagem();
                    $viagem = $action->handle($data);
                    Log::info("Viagem Nº " . $data['numero_viagem'] . " criada");
            }

            try {
                $carga = $this->cargaService->create($data['destino'], $viagem);
            } catch (\Exception $e) {
                Log::error("Erro ao criar/atualizar carga para a viagem Nº " . $data['numero_viagem'], [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'error' => $e->getMessage(),
                    'destino' => $data['destino'] ?? null,
                ]);
            }

            if ($carga) {
                Log::alert("Não foi possível criar carga da viagem Nº " . $data['numero_viagem']);
            }

            $this->setSuccess("Viagem Nº " . $data['numero_viagem'] . " criada");

            return $viagem;
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar ou criar viagem ", [
                'metodo'        => __METHOD__ . '@' . __LINE__,
                'error'         => $e->getMessage(),
                'viagem_numero' => $data['numero_viagem'] ?? null,
                'data'          => $data,
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    public function marcarViagemComoConferida(Models\Viagem $viagem)
    {

        try {
            $viagem = (new Actions\ViagemConferida())->handle($viagem);
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
            $viagem = (new Actions\ViagemNãoConferida())->handle($viagem);
            $this->setSuccess('Viagem marcada como não conferida!');
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

    public function getKmCadastroIntegrado() {}
}
