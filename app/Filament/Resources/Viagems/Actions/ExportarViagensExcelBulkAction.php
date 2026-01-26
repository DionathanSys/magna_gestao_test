<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models\Viagem;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarViagensExcelBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-excel')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a exportar ' . $records->count() . ' viagen(s) para Excel.'
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
            $sheet->setTitle('Viagens');

        // Definir cabeçalhos
        $headers = [
            'A' => 'ID',
            'B' => 'Placa',
            'C' => 'Nº Viagem',
            'D' => 'Integrado',
            'E' => 'Doc. Transporte',
            'F' => 'Km Rodado',
            'G' => 'Km Pago',
            'H' => 'Km Cadastro',
            'I' => 'Km Cobrar',
            'J' => 'Km Dispersão',
            'K' => 'Dispersão %',
            'L' => 'Motivo Divergência',
            'M' => 'Data Competência',
            'N' => 'Data Início',
            'O' => 'Data Fim',
            'P' => 'Conferido',
            'Q' => 'Cliente',
            'R' => 'Condutor',
            'S' => 'Unidade Negócio',
        ];

        // Escrever cabeçalhos
        $row = 1;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $row, $header);
        }

        // Estilizar cabeçalho
        $headerRange = 'A1:S1';
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
        $records->load(['veiculo:id,placa', 'cargas.integrado:id,nome,municipio']);

        // Preencher dados
        $row = 2;
        foreach ($records as $record) {
            // Formatar integrados
            $integrados = $record->cargas
                ->pluck('integrado')
                ->filter()
                ->map(fn($int) => "{$int->nome} - {$int->municipio}")
                ->unique()
                ->implode('; ');

            $sheet->setCellValueExplicit('A' . $row, $record->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('B' . $row, $record->veiculo->placa ?? '');
            $sheet->setCellValue('C' . $row, $record->numero_viagem);
            $sheet->setCellValue('D' . $row, $integrados ?: 'Sem Carga Vinculada');
            $sheet->setCellValue('E' . $row, $record->documento_transporte ?? 'Sem Doc. Transp.');
            $sheet->setCellValue('F' . $row, number_format($record->km_rodado, 2, ',', '.'));
            $sheet->setCellValue('G' . $row, number_format($record->km_pago, 2, ',', '.'));
            $sheet->setCellValue('H' . $row, number_format($record->km_cadastro, 2, ',', '.'));
            $sheet->setCellValue('I' . $row, number_format($record->km_cobrar, 2, ',', '.'));
            $sheet->setCellValue('J' . $row, number_format($record->km_dispersao, 2, ',', '.'));
            $sheet->setCellValue('K' . $row, number_format($record->dispersao_percentual, 2, ',', '.') . '%');
            $sheet->setCellValue('L' . $row, $record->motivo_divergencia?->value ?? '');
            $sheet->setCellValue('M' . $row, $record->data_competencia ? \Carbon\Carbon::parse($record->data_competencia)->format('d/m/Y') : '');
            $sheet->setCellValue('N' . $row, $record->data_inicio ? \Carbon\Carbon::parse($record->data_inicio)->format('d/m/Y H:i') : '');
            $sheet->setCellValue('O' . $row, $record->data_fim ? \Carbon\Carbon::parse($record->data_fim)->format('d/m/Y H:i') : '');
            $sheet->setCellValue('P' . $row, $record->conferido ? 'Sim' : 'Não');
            $sheet->setCellValue('Q' . $row, $record->cliente ?? '');
            $sheet->setCellValue('R' . $row, $record->condutor ?? '');
            $sheet->setCellValue('S' . $row, $record->unidade_negocio ?? '');

            $row++;
            
            // Liberar memória a cada 100 registros
            if ($row % 100 === 0) {
                $spreadsheet->garbageCollect();
            }
        }

        // Aplicar bordas em todas as células com dados
        $dataRange = 'A1:S' . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Auto-ajustar largura das colunas
        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Congelar primeira linha
        $sheet->freezePane('A2');

        // Criar response de download
        $fileName = 'viagens_' . date('Y-m-d_His') . '.xlsx';

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
