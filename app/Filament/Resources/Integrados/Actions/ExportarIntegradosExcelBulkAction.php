<?php

namespace App\Filament\Resources\Integrados\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarIntegradosExcelBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-excel')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records) => 'Você está prestes a exportar '.$records->count().' integrado(s) para Excel.')
            ->action(function (Collection $records) {
                if ($records->count() > 5000) {
                    Notification::make()
                        ->danger()
                        ->title('Muitos registros')
                        ->body('Por favor, selecione no máximo 5000 registros para exportar.')
                        ->send();

                    return null;
                }

                return static::exportToExcel($records);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function exportToExcel(Collection $records): StreamedResponse
    {
        set_time_limit(300);
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Integrados');

            $headers = [
                'A' => 'ID',
                'B' => 'Código',
                'C' => 'Nome',
                'D' => 'Documento',
                'E' => 'KM Rota',
                'F' => 'Município',
                'G' => 'Estado',
                'H' => 'Latitude',
                'I' => 'Longitude',
                'J' => 'Cliente',
                'K' => 'Alerta Viagem',
                'L' => 'Criado em',
                'M' => 'Atualizado em',
            ];

            foreach ($headers as $column => $header) {
                $sheet->setCellValue($column.'1', $header);
            }

            $sheet->getStyle('A1:M1')->applyFromArray([
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

            $row = 2;

            foreach ($records as $record) {
                $sheet->setCellValueExplicit('A'.$row, (string) $record->id, DataType::TYPE_NUMERIC);
                $sheet->setCellValue('B'.$row, $record->codigo ?? '');
                $sheet->setCellValue('C'.$row, $record->nome ?? '');
                $sheet->setCellValueExplicit('D'.$row, $record->documento ?? '', DataType::TYPE_STRING);
                $sheet->setCellValue('E'.$row, is_null($record->km_rota) ? '' : number_format((float) $record->km_rota, 2, ',', '.'));
                $sheet->setCellValue('F'.$row, $record->municipio ?? '');
                $sheet->setCellValue('G'.$row, $record->estado ?? '');
                $sheet->setCellValue('H'.$row, $record->latitude ?? '');
                $sheet->setCellValue('I'.$row, $record->longitude ?? '');
                $sheet->setCellValue('J'.$row, $record->cliente?->value ?? $record->cliente ?? '');
                $sheet->setCellValue('K'.$row, $record->alerta_viagem ? 'Sim' : 'Não');
                $sheet->setCellValue('L'.$row, $record->created_at?->format('d/m/Y H:i') ?? '');
                $sheet->setCellValue('M'.$row, $record->updated_at?->format('d/m/Y H:i') ?? '');

                $row++;

                if ($row % 100 === 0) {
                    $spreadsheet->garbageCollect();
                }
            }

            $sheet->getStyle('A1:M'.($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $sheet->freezePane('A2');

            $fileName = 'integrados_'.now()->format('Y-m-d_His').'.xlsx';

            return new StreamedResponse(
                function () use ($spreadsheet) {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                    'Cache-Control' => 'max-age=0',
                ],
            );
        } finally {
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }
}
