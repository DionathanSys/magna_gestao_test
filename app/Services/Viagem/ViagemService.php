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

            switch (true) {
                case ($viagem && $viagem->conferido == false):
                    Log::info("Viagem Nº " . $viagem['numero_viagem'] . " atualizada");
                    $this->update($viagem, $data);
                    break;
                case ($viagem && $viagem->conferido == true):
                    Log::info("Viagem Nº " . $viagem['numero_viagem'] . " já conferida, não será atualizado");
                    break;
                default:
                    $viagem = $this->create($data);
                    Log::info("Viagem Nº " . $data['numero_viagem'] . " criada");
                    $carga = $this->cargaService->create($data['integrado'], $viagem);
            }

            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
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
