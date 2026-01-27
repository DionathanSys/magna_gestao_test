<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Models\ViagemBugio;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarViagemBugioExcelBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-excel')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a exportar ' . $records->count() . ' viagem(ns) Bugio para Excel.'
            )
            ->action(function (Collection $records) {
                // Limitar exportação para evitar problemas de memória
                if ($records->count() > 5000) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Muitos registros')
                        ->body('Por favor, selecione no máximo 5000 registros para exportar.')
                        ->send();
                    return;
                }
                
                return static::exportToExcel($records);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function exportToExcel(Collection $records): StreamedResponse
    {
        // Aumentar limite de memória temporariamente
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        
        try {
            $spreadsheet = new Spreadsheet();
            
            // Otimizações do PhpSpreadsheet
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
            
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Viagens Bugio');

            // Definir cabeçalhos
            $headers = [
                'A' => 'ID',
                'B' => 'Placa',
                'C' => 'Integrados',
                'D' => 'Nº Doc. Frete',
                'E' => 'Nro Notas',
                'F' => 'Nº Sequencial',
                'G' => 'Tipo Doc.',
                'H' => 'Data Viagem',
                'I' => 'Peso',
                'J' => 'Km Pago',
                'K' => 'Frete',
                'L' => 'Motorista',
                'M' => 'Status',
                'N' => 'Viagem Vinculada',
                'O' => 'Doc. Frete Vinculado',
                'P' => 'Criado Por',
                'Q' => 'Criado Em',
                'R' => 'Atualizado Em',
            ];

            // Escrever cabeçalhos
            $row = 1;
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . $row, $header);
            }

            // Estilizar cabeçalho
            $headerRange = 'A1:R1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Carregar relacionamentos necessários
            $records->load(['veiculo:id,placa', 'viagem:id,numero_viagem', 'documento:id,numero_documento', 'creator:id,name']);

            // Preencher dados
            $row = 2;
            foreach ($records as $record) {
                // Formatar integrados
                $destinos = $record->destinos;
                $integrados = '-';
                if (!empty($destinos)) {
                    if (isset($destinos['integrado_nome'])) {
                        // É um único destino
                        $integrados = $destinos['integrado_nome'];
                    } else {
                        // É um array de destinos
                        $integrados = collect($destinos)
                            ->pluck('integrado_nome')
                            ->filter()
                            ->implode('; ');
                    }
                }

                // Formatar notas
                $nroNotas = $record->nro_notas;
                $notasFormatadas = '-';
                if (!empty($nroNotas)) {
                    if (is_string($nroNotas)) {
                        $notasFormatadas = $nroNotas;
                    } else {
                        $notasFormatadas = collect($nroNotas)
                            ->filter()
                            ->implode('; ');
                    }
                }

                // Formatar número sequencial
                $numeroSequencial = $record->numero_sequencial ? str_pad($record->numero_sequencial, 6, '0', STR_PAD_LEFT) : '-';

                // Extrair tipo documento
                $tipoDocumento = '-';
                $peso = '-';
                if ($record->info_adicionais) {
                    $info = is_string($record->info_adicionais) 
                        ? json_decode($record->info_adicionais, true) 
                        : $record->info_adicionais;
                    
                    if (is_array($info)) {
                        if (isset($info['tipo_documento'])) {
                            $tipoDocumento = $info['tipo_documento'];
                            
                            // Se for CTe Complemento, retorna o valor de cte_referencia
                            if ($tipoDocumento === 'CTe Complemento' && isset($info['cte_referencia'])) {
                                $tipoDocumento = 'Complemto ao CTe ' . $info['cte_referencia'];
                            }
                        }
                        
                        // Extrair peso
                        if (isset($info['peso'])) {
                            $peso = number_format($info['peso'], 0, ',', '.');
                        }
                    }
                }

                // Formatar status
                $statusMap = [
                    'pendente' => 'Pendente',
                    'em_andamento' => 'CTe solicitado',
                    'concluido' => 'CTe emitido',
                    'cancelada' => 'Cancelada',
                ];
                $status = $statusMap[$record->status] ?? $record->status;

                $sheet->setCellValueExplicit('A' . $row, $record->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->setCellValue('B' . $row, $record->veiculo->placa ?? '');
                $sheet->setCellValue('C' . $row, $integrados);
                $sheet->setCellValue('D' . $row, $record->nro_documento ?? '-');
                $sheet->setCellValue('E' . $row, $notasFormatadas);
                $sheet->setCellValue('F' . $row, $numeroSequencial);
                $sheet->setCellValue('G' . $row, $tipoDocumento);
                $sheet->setCellValue('H' . $row, $record->data_competencia ? \Carbon\Carbon::parse($record->data_competencia)->format('d/m/Y') : '');
                $sheet->setCellValue('I' . $row, $peso);
                $sheet->setCellValue('J' . $row, number_format($record->km_pago, 0, ',', '.'));
                $sheet->setCellValue('K' . $row, 'R$ ' . number_format($record->frete / 100, 2, ',', '.'));
                $sheet->setCellValue('L' . $row, $record->condutor ?? '');
                $sheet->setCellValue('M' . $row, $status);
                $sheet->setCellValue('N' . $row, $record->viagem->numero_viagem ?? '-');
                $sheet->setCellValue('O' . $row, $record->documento->numero_documento ?? '-');
                $sheet->setCellValue('P' . $row, $record->creator->name ?? '');
                $sheet->setCellValue('Q' . $row, $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') : '');
                $sheet->setCellValue('R' . $row, $record->updated_at ? \Carbon\Carbon::parse($record->updated_at)->format('d/m/Y H:i') : '');

                $row++;
                
                // Liberar memória a cada 100 registros
                if ($row % 100 === 0) {
                    $spreadsheet->garbageCollect();
                }
            }

            // Aplicar bordas em todas as células com dados
            $dataRange = 'A1:R' . ($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Auto-ajustar largura das colunas
            foreach (range('A', 'R') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Congelar primeira linha
            $sheet->freezePane('A2');

            // Criar response de download
            $fileName = 'viagens_bugio_' . date('Y-m-d_His') . '.xlsx';

            return new StreamedResponse(
                function () use ($spreadsheet) {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Cache-Control' => 'max-age=0',
                ]
            );
        } finally {
            // Restaurar limite de memória original
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }
}
