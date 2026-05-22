<?php

namespace App\Services\Pneus;

use App\Models\HistoricoMovimentoPneu;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioMovimentacoesPneusPdfService
{
    public function getMovimentacoes(string $dataInicial, string $dataFinal): Collection
    {
        return HistoricoMovimentoPneu::query()
            ->with([
                'veiculo:id,placa',
                'pneu:id,numero_fogo',
            ])
            ->whereBetween('created_at', [
                Carbon::parse($dataInicial)->startOfDay(),
                Carbon::parse($dataFinal)->endOfDay(),
            ])
            ->orderBy('veiculo_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function agruparPorVeiculo(Collection $movimentacoes): Collection
    {
        return $movimentacoes
            ->groupBy(fn (HistoricoMovimentoPneu $movimento) => $movimento->veiculo_id ?? 'sem-veiculo')
            ->map(function (Collection $grupo) {
                /** @var HistoricoMovimentoPneu $primeiro */
                $primeiro = $grupo->first();

                return [
                    'veiculo_id' => $primeiro?->veiculo_id,
                    'placa' => $primeiro?->veiculo?->placa ?? 'Sem veículo',
                    'total_movimentacoes' => $grupo->count(),
                    'movimentacoes' => $grupo,
                ];
            })
            ->sortBy('placa')
            ->values();
    }

    public function gerarPdf(string $dataInicial, string $dataFinal): mixed
    {
        $movimentacoes = $this->getMovimentacoes($dataInicial, $dataFinal);
        $veiculos = $this->agruparPorVeiculo($movimentacoes);

        $pdf = Pdf::loadView('pdf.relatorio-movimentacoes-pneus', [
            'veiculos' => $veiculos,
            'dataInicial' => Carbon::parse($dataInicial)->format('d/m/Y'),
            'dataFinal' => Carbon::parse($dataFinal)->format('d/m/Y'),
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
            'totalMovimentacoes' => $movimentacoes->count(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'relatorio_movimentacoes_pneus_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
