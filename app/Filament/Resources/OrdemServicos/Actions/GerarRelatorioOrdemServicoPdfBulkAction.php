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
                        'matricial' => 'Impressora Matricial',
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
        // Carregar relacionamentos necessários
        $ordensServico = OrdemServico::whereIn('id', $records->pluck('id'))
            ->with([
                'veiculo:id,placa',
                'itens.servico:id,descricao',
                'itens.comentarios.creator:id,name',
                'sankhyaId:id,ordem_servico_id,ordem_sankhya_id',
                'parceiro:id,nome'
            ])
            ->orderBy('id', 'asc')
            ->get();

        // Selecionar a view baseada no modelo
        $view = $modelo === 'matricial' 
            ? 'pdf.relatorio-ordens-servico-matricial' 
            : 'pdf.relatorio-ordens-servico';

        // Gerar PDF
        $pdf = Pdf::loadView($view, [
            'ordensServico' => $ordensServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
        ]);

        // Configurar papel baseado no modelo
        if ($modelo === 'matricial') {
            $pdf->setPaper('a4', 'portrait'); // Formato padrão para matricial
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        $sufixo = $modelo === 'matricial' ? '_matricial' : '';
        $fileName = 'relatorio_ordens_servico' . $sufixo . '_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
