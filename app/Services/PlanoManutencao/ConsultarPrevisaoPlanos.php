<?php

namespace App\Services\PlanoManutencao;

use App\Services\Veiculo\VeiculoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ConsultarPrevisaoPlanos
{
    protected VeiculoService    $veiculoService;
    protected array             $planosPreventivosPendentes = [];

    public function __construct(
        protected int $planoPreventivoId,
        protected int $kmIntervaloPlano,
        protected int $kmTolerancia = 2500
        )
    {
        $this->veiculoService = new VeiculoService();
    }

    public function exec(): array
    {
        $veiculosPlano = $this->getVeiculosPlano();

        $kmAtualVeiculos = $this->veiculoService
            ->getKmAtualVeiculos(
                $veiculosPlano
                    ->pluck('veiculo_id')
                    ->toArray()
            );

        foreach ($veiculosPlano as $veiculoPlano) {

            try {
                $previsaoPlano = $this->calcularPrevisaoPlano(
                    $veiculoPlano['veiculo_id'],
                    $kmAtualVeiculos[$veiculoPlano['veiculo_id']]['km_atual'],
                    $kmAtualVeiculos[$veiculoPlano['veiculo_id']]['km_medio']
                );

                if (!empty($previsaoPlano) && $previsaoPlano['km_restante'] <= $this->kmTolerancia) {
                    $previsaoPlano['placa'] = $kmAtualVeiculos[$veiculoPlano['veiculo_id']]['placa'];
                    ds($previsaoPlano)->label('Previsão Plano VeiculoID: ' . $veiculoPlano['veiculo_id']);
                    $this->planosPreventivosPendentes[] = $previsaoPlano;
                }


            } catch (\Exception $e) {

                Log::error('Erro ao calcular previsão de planos.', [
                    'plano_preventivo_id' => $this->planoPreventivoId,
                    'veiculo_id' => $veiculoPlano['veiculo_id'],
                    'error' => $e->getMessage()
                ]);
                continue;

            }
        }

        return $this->planosPreventivosPendentes;

    }

    private function calcularPrevisaoPlano(int $veiculoId, int $kmAtual, int $kmMedio): array
    {
        $ultimaExecucao = \App\Models\PlanoManutencaoOrdemServico::query()
            ->select('id', 'data_execucao', 'km_execucao')
            ->where('plano_preventivo_id', $this->planoPreventivoId)
            ->where('veiculo_id', $veiculoId)
            ->orderBy('data_execucao', 'desc')
            ->first()
            ?->toArray();

        if (!$ultimaExecucao) {
            Log::info('Nenhuma execução encontrada para o veículo.', [
                'plano_preventivo_id' => $this->planoPreventivoId,
                'veiculo_id' => $veiculoId
            ]);
            return [];
        }

        $kmRestante = ($ultimaExecucao['km_execucao'] + $this->kmIntervaloPlano) - $kmAtual;
        $diasRestantes = $kmRestante <= 0 ? 0 : ceil($kmRestante / $kmMedio);
        $dataPrevista = now()->addDays($diasRestantes);

        return [
            'plano_preventivo_id' => $this->planoPreventivoId,
            'veiculo_id'          => $veiculoId,
            'km_atual'            => $kmAtual,
            'ultima_execucao'     => $ultimaExecucao,
            'data_prevista'       => $dataPrevista,
            'km_proximo'          => $ultimaExecucao['km_execucao'] + $this->kmIntervaloPlano,
            'km_intervalo'        => $this->kmIntervaloPlano,
            'km_restante'         => $kmRestante,
            'km_tolerancia'       => $this->kmTolerancia,
        ];

    }

    private function getVeiculosPlano(): Collection
    {
        return \App\Models\PlanoManutencaoVeiculo::query()
            ->select('id', 'veiculo_id')
            ->where('plano_preventivo_id', $this->planoPreventivoId)
            ->get();
    }
}
