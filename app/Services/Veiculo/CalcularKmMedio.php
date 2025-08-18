<?php

namespace App\Services\Veiculo;

use App\Models\HistoricoQuilometragem;
use Illuminate\Support\Facades\Log;

class CalcularKmMedio
{
    protected HistoricoQuilometragem $historicoQuilometragem;

    public function __construct(protected $veiculoId)
    {
        $this->historicoQuilometragem = new HistoricoQuilometragem();
    }

    public function exec(): ?float
    {
        $historico = $this->getHistoricoQuilometragem();

        return $this->calcularMediaQuilometragem($historico);
    }

    private function getHistoricoQuilometragem(): array
    {
        return $this->historicoQuilometragem
            ->select('data_referencia', 'quilometragem')
            ->where('veiculo_id', $this->veiculoId)
            ->whereBetween('data_referencia', [
                now()->subDays(14),
                now()
            ])
            ->orderBy('data_referencia', 'asc')
            ->get()
            ->toArray();
    }

    private function calcularMediaQuilometragem(array $historico): ?float
    {
        if (empty($historico) || count($historico) < 2) {
            Log::alert('Não foi possível calcular a média de quilometragem, pois não há registros suficientes.', [
                'veiculo_id' => $this->veiculoId,
                'historico' => $historico
            ]);
            return null;
        }

        $kmPercorrido = end($historico)['quilometragem'] - reset($historico)['quilometragem'];

        //Converter strings para objetos Carbon para calcular a diferença de dias
        $dataFinal = \Carbon\Carbon::parse(end($historico)['data_referencia']);
        $dataInicial = \Carbon\Carbon::parse(reset($historico)['data_referencia']);

        $diffData = $dataInicial->diffInDays($dataFinal);

        if ($diffData <= 0 || $kmPercorrido <= 0) {
            Log::alert('Valores inválidos para cálculo de quilometragem média.', [
                'veiculo_id' => $this->veiculoId,
                'historico' => $historico,
            ]);
            return null;
        }

        // Calcular quilometragem média por dia
        $kmMedioPorDia = $kmPercorrido / $diffData;

        return $kmMedioPorDia;
    }
}
