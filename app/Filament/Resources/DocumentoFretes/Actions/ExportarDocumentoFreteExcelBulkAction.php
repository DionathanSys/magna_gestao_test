<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Models\DocumentoFrete;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarDocumentoFreteExcelBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-excel')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a exportar ' . $records->count() . ' documento(s) de frete para Excel.'
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
            $sheet->setTitle('Documentos Frete');

            // Definir cabeçalhos
            $headers = [
                'A' => 'ID',
                'B' => 'Placa',
                'C' => 'Nro. Documento',
                'D' => 'Nro. Doc. Transp.',
                'E' => 'Tipo Documento',
                'F' => 'Dt. Emissão',
                'G' => 'Vlr. Total',
                'H' => 'Vlr. ICMS',
                'I' => 'Frete Líquido',
                'J' => 'Parceiro Origem',
                'K' => 'Parceiro Destino',
                'L' => 'Viagem ID',
                'M' => 'Resultado Período ID',
                'N' => 'Criado Em',
                'O' => 'Atualizado Em',
            ];

            // Escrever cabeçalhos
            $row = 1;
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . $row, $header);
            }

            // Estilizar cabeçalho
            $headerRange = 'A1:O1';
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
            $records->load(['veiculo:id,placa', 'resultadoPeriodo:id,data_inicio']);

            // Preencher dados
            $row = 2;
            foreach ($records as $record) {
                $sheet->setCellValueExplicit('A' . $row, $record->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->setCellValue('B' . $row, $record->veiculo->placa ?? '');
                $sheet->setCellValue('C' . $row, $record->numero_documento ?? '');
                $sheet->setCellValue('D' . $row, $record->documento_transporte ?? '');
                $sheet->setCellValue('E' . $row, $record->tipo_documento?->value ?? $record->tipo_documento ?? '');
                $sheet->setCellValue('F' . $row, $record->data_emissao ? \Carbon\Carbon::parse($record->data_emissao)->format('d/m/Y') : '');
                $sheet->setCellValue('G' . $row, 'R$ ' . number_format($record->valor_total / 100, 2, ',', '.'));
                $sheet->setCellValue('H' . $row, 'R$ ' . number_format($record->valor_icms / 100, 2, ',', '.'));
                $sheet->setCellValue('I' . $row, 'R$ ' . number_format($record->valor_liquido / 100, 2, ',', '.'));
                $sheet->setCellValue('J' . $row, $record->parceiro_origem ?? '');
                $sheet->setCellValue('K' . $row, $record->parceiro_destino ?? '');
                $sheet->setCellValue('L' . $row, $record->viagem_id ?? '');
                $sheet->setCellValue('M' . $row, $record->resultado_periodo_id ?? '');
                $sheet->setCellValue('N' . $row, $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') : '');
                $sheet->setCellValue('O' . $row, $record->updated_at ? \Carbon\Carbon::parse($record->updated_at)->format('d/m/Y H:i') : '');

                $row++;
                
                // Liberar memória a cada 100 registros
                if ($row % 100 === 0) {
                    $spreadsheet->garbageCollect();
                }
            }

            // Aplicar bordas em todas as células com dados
            $dataRange = 'A1:O' . ($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Auto-ajustar largura das colunas
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Congelar primeira linha
            $sheet->freezePane('A2');

            // Criar response de download
            $fileName = 'documentos_frete_' . date('Y-m-d_His') . '.xlsx';

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
