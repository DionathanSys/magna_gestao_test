<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Models\OrdemServico;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
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
            ->accessSelectedRecords()
            ->form([
                Select::make('modelo')
                    ->label('Modelo do Relatório')
                    ->options([
                        'padrao' => 'Padrão (A4)',
                        'termico' => 'Térmico (80mm)',
                    ])
                    ->default('padrao')
                    ->required()
                    ->native(false)
                    ->helperText('Escolha o formato de impressão do relatório'),
            ])
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a gerar um relatório PDF com ' . $records->count() . ' ordem(ns) de serviço.'
            )
            ->action(function (Collection $records, array $data) {
                return static::gerarPdf($records, $data['modelo']);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function gerarPdf(Collection $records, string $modelo = 'padrao')
    {
    {
        // Carregar relacionamentos necessários
        $ordensServico = OrdemServico::whereIn('id', $records->pluck('id'))
            ->with([
                'veiculo:id,placa',
                'itens.servico:id,descricao',
                'itens.comentarios.user:id,name',
                'sankhyaId:id,ordem_servico_id,ordem_sankhya_id',
                'parceiro:id,nome'
            ])
            ->orderBy('id', 'asc')
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
