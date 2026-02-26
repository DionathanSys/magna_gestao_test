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
            ->schema([
                Toggle::make('exibir_vinculos')
                    ->label('Exibir Documento Frete ID e Viagem ID')
                    ->default(true)
                    ->helperText('Desmarque para ocultar as colunas de Documento Frete ID e Viagem ID no relatório.'),
            ])
            ->modalDescription(fn(Collection $records) => 'Será gerado um relatório PDF com ' . $records->count() . ' registro(s) selecionado(s), agrupados por veículo.')
            ->action(function (Collection $records, array $data) {
                if ($records->count() > 500) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Muitos registros selecionados')
                        ->body('Selecione no máximo 500 registros por geração de relatório PDF.')
                        ->send();
                    return;
                }

                return static::gerarPdf($records, $data['exibir_vinculos'] ?? true);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function gerarPdf(Collection $records, bool $exibirVinculos): mixed
    {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $ids = $records->pluck('id')->toArray();
            unset($records); // libera a collection original

            // Buscar apenas os campos necessários + selecionar somente colunas usadas
            $veiculoIds = ViagemBugio::whereIn('id', $ids)
                ->distinct()
                ->pluck('veiculo_id')
                ->toArray();

            // Pré-carregar placas para evitar N+1
            $placas = \App\Models\Veiculo::whereIn('id', $veiculoIds)
                ->pluck('placa', 'id')
                ->toArray();

            $veiculos = [];

            // Processar por veículo em chunks para liberar memória
            foreach ($veiculoIds as $veiculoId) {
                $placa = $placas[$veiculoId] ?? 'Sem Placa';
                $totalFrete = 0;
                $registrosFormatados = [];

                ViagemBugio::whereIn('id', $ids)
                    ->where('veiculo_id', $veiculoId)
                    ->select([
                        'id', 'veiculo_id', 'nro_documento', 'numero_sequencial',
                        'nro_notas', 'data_competencia', 'destinos', 'frete',
                        'documento_frete_id', 'viagem_id', 'info_adicionais',
                    ])
                    ->orderBy('numero_sequencial', 'asc')
                    ->chunk(200, function ($chunk) use (&$registrosFormatados, &$totalFrete) {
                        foreach ($chunk as $registro) {
                            $totalFrete += (float) $registro->frete;
                            $registrosFormatados[] = static::formatarRegistro($registro);
                        }
                    });

                $veiculos[] = [
                    'placa' => $placa,
                    'total_frete' => $totalFrete,
                    'qtde_documentos' => count($registrosFormatados),
                    'registros' => $registrosFormatados,
                ];

                unset($registrosFormatados); // libera array do veículo já inserido
            }

            unset($ids, $veiculoIds, $placas);

            $pdf = Pdf::loadView('pdf.relatorio-documentos-frete', [
                'veiculos' => $veiculos,
                'exibirVinculos' => $exibirVinculos,
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ]);

            $pdf->setPaper('a4', 'landscape');
            $pdf->setOption('isPhpEnabled', false);
            $pdf->setOption('isFontSubsettingEnabled', true);

            unset($veiculos); // libera dados após renderização da view

            $fileName = 'relatorio_documentos_frete_' . now()->format('Y-m-d_His') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
                unset($pdf);
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        } finally {
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }

    protected static function formatarRegistro(ViagemBugio $registro): array
    {
        // Formatar nro_notas (campo JSON array)
        $nroNotas = $registro->nro_notas;
        $nroNotasFormatado = '-';
        if (is_array($nroNotas) && count($nroNotas) > 0) {
            $nroNotasFormatado = implode(', ', array_filter($nroNotas));
        }

        // Formatar destinos (campo JSON)
        $destinos = $registro->destinos;
        $destinoFormatado = '-';
        if (is_array($destinos) && count($destinos) > 0) {
            if (isset($destinos['integrado_nome'])) {
                $nome = $destinos['integrado_nome'] ?? '';
                $municipio = $destinos['municipio'] ?? '';
                $destinoFormatado = trim($nome . ' - ' . $municipio, ' - ');
            } else {
                $partes = [];
                foreach ($destinos as $d) {
                    $nome = $d['integrado_nome'] ?? '';
                    $municipio = $d['municipio'] ?? '';
                    $texto = trim($nome . ' - ' . $municipio, ' - ');
                    if ($texto) {
                        $partes[] = $texto;
                    }
                }
                $destinoFormatado = implode('; ', $partes) ?: '-';
            }
        }

        // Formatar info_adicionais (campo JSON)
        $peso = '-';
        $tipoDocumento = '-';
        $tipoDocumentoFormatado = '-';
        
        $infoAdicionais = $registro->info_adicionais;
        if (is_array($infoAdicionais) && count($infoAdicionais) > 0) {
            // Extrair peso
            if (isset($infoAdicionais['peso']) && !empty($infoAdicionais['peso'])) {
                $peso = number_format((float) $infoAdicionais['peso'], 0, ',', '.');
            }
            
            // Extrair tipo_documento e cte_referencia
            if (isset($infoAdicionais['tipo_documento'])) {
                $tipoDocumento = $infoAdicionais['tipo_documento'];
                $tipoDocumentoFormatado = $tipoDocumento;
                
                // Se cte_referencia tiver valor, concatenar
                if (!empty($infoAdicionais['cte_referencia'])) {
                    $tipoDocumentoFormatado = $tipoDocumento . ' ' . $infoAdicionais['cte_referencia'];
                }
            }
        }

        return [
            'id' => $registro->id,
            'nro_documento' => $registro->nro_documento,
            'numero_sequencial' => $registro->numero_sequencial,
            'nro_notas_formatado' => $nroNotasFormatado,
            'data_competencia' => $registro->data_competencia,
            'destino_formatado' => $destinoFormatado,
            'frete' => $registro->frete,
            'documento_frete_id' => $registro->documento_frete_id,
            'viagem_id' => $registro->viagem_id,
            'peso' => $peso,
            'tipo_documento' => $tipoDocumentoFormatado,
        ];
    }
}
