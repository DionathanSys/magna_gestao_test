<?php

namespace App\Services\PlanoManutencao;

use App\Models;
use App\Services;

class CalcularPrevisaoPlano
{
    // protected Models\PlanoManutencaoOrdemServico $planoOrdemServico;
    protected Models\PlanoPreventivo $planoPreventivo;
    protected Services\Veiculo\VeiculoService $veiculoService;
    protected float $kmMedio;

    public function __construct(protected Models\PlanoManutencaoVeiculo $planoVeiculo)
    {
        $this->planoPreventivo = $this->planoVeiculo->planoPreventivo;
        $this->veiculoService = new Services\Veiculo\VeiculoService();
        $this->kmMedio = $this->veiculoService
            ->getKmMedio($this->planoVeiculo->veiculo_id);
    }

    public function exec(): array
    {
        $kmRestante = $this->planoVeiculo->quilometragem_restante;
        $diasRestantes = $kmRestante <= 0 ? 0 : ceil($kmRestante / $this->kmMedio);
        $dataPrevista = now()->addDays($diasRestantes)->format('d/m/Y');

        return [
            'plano_preventivo_id'   => $this->planoVeiculo->plano_preventivo_id,
            'veiculo_id'            => $this->planoVeiculo->veiculo_id,
            'km_execucao'           => $this->planoVeiculo->ultima_execucao->km_execucao ?? 0,
            'km_proxima_execucao'   => $this->planoVeiculo->proxima_execucao,
            'km_restante'           => $this->planoVeiculo->quilometragem_restante,
            'data_prevista'         => $dataPrevista,
            'intervalo'             => $this->planoPreventivo->intervalo,
            'descricao'             => $this->planoPreventivo->descricao,
            'itens'                 => $this->planoPreventivo->itens,
        ];
    }

}
