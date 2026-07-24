<?php

namespace App\Services\Veiculo;

use App\Models\Veiculo;
use Illuminate\Support\Facades\Log;

class AtualizarKmMedio
{
    protected Veiculo $veiculo;

    public function __construct(protected $veiculoId)
    {
        $this->veiculo = Veiculo::findOrFail($veiculoId);
    }

    public function exec(float $kmMedio): void
    {
        try {
            $this->veiculo->update([
                'km_medio' => $kmMedio,
                'data_km_medio' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar quilometragem média do veículo.', [
                'veiculo_id' => $this->veiculoId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
