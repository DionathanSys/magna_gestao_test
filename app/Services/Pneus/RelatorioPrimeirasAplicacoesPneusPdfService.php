<?php

namespace App\Services\Pneus;

use App\Models\HistoricoMovimentoPneu;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioPrimeirasAplicacoesPneusPdfService
{
    public function getPrimeirasAplicacoes(string $dataInicial, string $dataFinal): Collection
    {
        return HistoricoMovimentoPneu::query()
            ->with([
                'ciclo.desenhoPneu',
                'pneu.marcaCatalogo',
                'pneu.medidaCatalogo',
                'pneu.modeloCatalogo',
                'veiculo',
            ])
            ->where('tipo_evento', 'APLICACAO')
            ->whereNotNull('pneu_ciclo_id')
            ->whereBetween('data_inicial', [
                Carbon::parse($dataInicial)->startOfDay(),
                Carbon::parse($dataFinal)->endOfDay(),
            ])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('historico_movimento_pneus as aplicacoes_anteriores')
                    ->whereColumn('aplicacoes_anteriores.pneu_ciclo_id', 'historico_movimento_pneus.pneu_ciclo_id')
                    ->where('aplicacoes_anteriores.tipo_evento', 'APLICACAO')
                    ->where(function ($query): void {
                        $query->whereColumn('aplicacoes_anteriores.data_inicial', '<', 'historico_movimento_pneus.data_inicial')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('aplicacoes_anteriores.data_inicial', 'historico_movimento_pneus.data_inicial')
                                    ->whereColumn('aplicacoes_anteriores.id', '<', 'historico_movimento_pneus.id');
                            });
                    });
            })
            ->orderBy('data_inicial')
            ->orderBy('id')
            ->get();
    }

    public function gerarPdf(string $dataInicial, string $dataFinal): mixed
    {
        $aplicacoes = $this->getPrimeirasAplicacoes($dataInicial, $dataFinal);

        $pdf = Pdf::loadView('pdf.relatorio-primeiras-aplicacoes-pneus', [
            'aplicacoes' => $aplicacoes,
            'dataInicial' => Carbon::parse($dataInicial)->format('d/m/Y'),
            'dataFinal' => Carbon::parse($dataFinal)->format('d/m/Y'),
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'relatorio_primeiras_aplicacoes_pneus_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
