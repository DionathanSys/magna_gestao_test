<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Models\ViagemBugio;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;

class ExportarRelatorioDocumentosFreteAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-relatorio-pdf')
            ->label('Relatório PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('danger')
            ->form([
                Toggle::make('exibir_vinculos')
                    ->label('Exibir Documento Frete ID e Viagem ID')
                    ->default(true)
                    ->helperText('Desmarque para ocultar as colunas de Documento Frete ID e Viagem ID no relatório.'),
            ])
            ->modalDescription(fn(Collection $records) => 'Será gerado um relatório PDF com ' . $records->count() . ' registro(s) selecionado(s), agrupados por veículo.')
            ->action(function (Collection $records, array $data) {
                return static::gerarPdf($records, $data['exibir_vinculos'] ?? true);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function gerarPdf(Collection $records, bool $exibirVinculos): mixed
    {
        $viagens = ViagemBugio::whereIn('id', $records->pluck('id'))
            ->with(['veiculo:id,placa'])
            ->orderBy('veiculo_id')
            ->orderBy('data_competencia', 'desc')
            ->get();

        // Agrupar por veículo
        $agrupados = $viagens->groupBy('veiculo_id');

        $veiculos = [];

        foreach ($agrupados as $veiculoId => $registrosVeiculo) {
            $placa = $registrosVeiculo->first()->veiculo->placa ?? 'Sem Placa';
            $totalFrete = $registrosVeiculo->sum('frete');

            $registrosFormatados = [];

            foreach ($registrosVeiculo as $registro) {
                // Formatar nro_notas (campo JSON array)
                $nroNotas = $registro->nro_notas;
                if (is_array($nroNotas) && count($nroNotas) > 0) {
                    $nroNotasFormatado = implode(', ', array_filter($nroNotas));
                } else {
                    $nroNotasFormatado = '-';
                }

                // Formatar destinos (campo JSON)
                $destinos = $registro->destinos;
                $destinoFormatado = '-';
                if (is_array($destinos) && count($destinos) > 0) {
                    if (isset($destinos['integrado_nome'])) {
                        // Destino único (associativo)
                        $nome = $destinos['integrado_nome'] ?? '';
                        $municipio = $destinos['municipio'] ?? '';
                        $destinoFormatado = trim($nome . ' - ' . $municipio, ' - ');
                    } else {
                        // Array de destinos
                        $destinoFormatado = collect($destinos)
                            ->map(function ($d) {
                                $nome = $d['integrado_nome'] ?? '';
                                $municipio = $d['municipio'] ?? '';
                                return trim($nome . ' - ' . $municipio, ' - ');
                            })
                            ->filter()
                            ->join('; ');
                    }
                }

                $registrosFormatados[] = [
                    'id' => $registro->id,
                    'nro_documento' => $registro->nro_documento,
                    'numero_sequencial' => $registro->numero_sequencial,
                    'nro_notas_formatado' => $nroNotasFormatado,
                    'data_competencia' => $registro->data_competencia,
                    'destino_formatado' => $destinoFormatado ?: '-',
                    'frete' => $registro->frete,
                    'documento_frete_id' => $registro->documento_frete_id,
                    'viagem_id' => $registro->viagem_id,
                ];
            }

            $veiculos[] = [
                'placa' => $placa,
                'total_frete' => $totalFrete,
                'qtde_documentos' => $registrosVeiculo->count(),
                'registros' => $registrosFormatados,
            ];
        }

        $pdf = Pdf::loadView('pdf.relatorio-documentos-frete', [
            'veiculos' => $veiculos,
            'exibirVinculos' => $exibirVinculos,
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'relatorio_documentos_frete_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
