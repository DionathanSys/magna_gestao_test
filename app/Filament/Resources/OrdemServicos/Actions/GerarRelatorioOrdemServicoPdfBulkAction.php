<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Models\OrdemServico;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class GerarRelatorioOrdemServicoPdfBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('gerar-relatorio-pdf')
            ->label('Gerar Relatório PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a gerar um relatório PDF com ' . $records->count() . ' ordem(ns) de serviço.'
            )
            ->action(function (Collection $records) {
                return static::gerarPdf($records);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function gerarPdf(Collection $records)
    {
        // Carregar relacionamentos necessários
        $ordensServico = OrdemServico::whereIn('id', $records->pluck('id'))
            ->with([
                'veiculo:id,placa',
                'itens.servico:id,descricao',
                'sankhyaId:id,ordem_servico_id,ordem_sankhya_id',
                'parceiro:id,nome'
            ])
            ->orderBy('id', 'desc')
            ->get();

        // Gerar PDF
        $pdf = Pdf::loadView('pdf.relatorio-ordens-servico', [
            'ordensServico' => $ordensServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $fileName = 'relatorio_ordens_servico_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
