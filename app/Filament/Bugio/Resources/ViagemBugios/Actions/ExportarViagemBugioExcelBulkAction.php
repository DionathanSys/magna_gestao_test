<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Models\Integrado;
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

            // Definir cabeçalhos conforme especificação
            $headers = [
                'A' => 'Tipo de Fr',
                'B' => 'Transporta',
                'C' => 'Nome Transportador',
                'D' => 'Placa',
                'E' => 'Nota F',
                'F' => 'Nº de viag',
                'G' => 'Destino',
                'H' => 'Local',
                'I' => 'N. Ext.',
                'J' => 'Status',
                'K' => 'Dta.criação',
                'L' => 'Valor CTRC',
                'M' => 'Vl. Recibo',
                'N' => 'KM',
                'O' => 'Entregas',
                'P' => 'Ad. Entreg',
                'Q' => 'Peso',
                'R' => 'Valor Brut',
                'S' => 'Frete Líqu',
                'T' => 'Valor do F',
            ];

            // Escrever cabeçalhos
            $row = 1;
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . $row, $header);
            }

            // Estilizar cabeçalho
            $headerRange = 'A1:T1';
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
            $records->load(['veiculo:id,placa']);

            // Preencher dados
            $row = 2;
            foreach ($records as $record) {
                // Extrair informações adicionais
                $info = null;
                $tipoDocumento = null;
                $peso = null;
                
                if ($record->info_adicionais) {
                    $info = is_string($record->info_adicionais) 
                        ? json_decode($record->info_adicionais, true) 
                        : $record->info_adicionais;
                    
                    if (is_array($info)) {
                        $tipoDocumento = $info['tipo_documento'] ?? null;
                        $peso = $info['peso'] ?? null;
                    }
                }

                // Buscar informações do integrado
                $destinos = $record->destinos;
                $integradoNome = '';
                $integradoMunicipio = '';
                
                if (!empty($destinos)) {
                    $integradoId = null;
                    
                    // Verificar se é um array associativo único ou array de arrays
                    if (isset($destinos['integrado_id'])) {
                        $integradoId = $destinos['integrado_id'];
                        $integradoNome = $destinos['integrado_nome'] ?? '';
                    } elseif (is_array($destinos) && count($destinos) > 0) {
                        // É um array de destinos, pegar o primeiro
                        $primeiroDestino = reset($destinos);
                        if (isset($primeiroDestino['integrado_id'])) {
                            $integradoId = $primeiroDestino['integrado_id'];
                            $integradoNome = $primeiroDestino['integrado_nome'] ?? '';
                        }
                    }
                    
                    // Buscar município do integrado
                    if ($integradoId) {
                        $integrado = Integrado::find($integradoId);
                        if ($integrado) {
                            $integradoMunicipio = $integrado->municipio ?? '';
                        }
                    }
                }

                // Formatar número sequencial
                $numeroSequencial = $record->numero_sequencial ? str_pad($record->numero_sequencial, 6, '0', STR_PAD_LEFT) : '';

                // Calcular Valor CTRC e Vl. Recibo
                $valorCtrc = 0;
                $valorRecibo = 0;
                
                if ($tipoDocumento === 'NFSe') {
                    $valorRecibo = $record->frete ?? 0;
                } else {
                    $valorCtrc = $record->frete ?? 0;
                }

                // Preencher as colunas
                $sheet->setCellValue('A' . $row, 150); // Tipo de Fr - valor padrão
                $sheet->setCellValue('B' . $row, 384033); // Transporta - valor padrão
                $sheet->setCellValue('C' . $row, 'MAGNABOSCO COM E TRANSPORTES LTDA'); // Nome Transportador - valor padrão
                $sheet->setCellValue('D' . $row, $record->veiculo->placa ?? ''); // Placa
                $sheet->setCellValue('E' . $row, $record->nro_documento ?? ''); // Nota F
                $sheet->setCellValue('F' . $row, $numeroSequencial); // Nº de viag
                $sheet->setCellValue('G' . $row, $integradoNome); // Destino
                $sheet->setCellValue('H' . $row, $integradoMunicipio); // Local
                $sheet->setCellValue('I' . $row, 'v'); // N. Ext. - valor padrão
                $sheet->setCellValue('J' . $row, ''); // Status - em branco
                $sheet->setCellValue('K' . $row, $record->data_competencia ? \Carbon\Carbon::parse($record->data_competencia)->format('d/m/Y') : ''); // Dta.criação
                $sheet->setCellValue('L' . $row, $valorCtrc); // Valor CTRC
                $sheet->setCellValue('M' . $row, $valorRecibo); // Vl. Recibo
                $sheet->setCellValue('N' . $row, $record->km_pago ?? 0); // KM
                $sheet->setCellValue('O' . $row, 1); // Entregas - valor padrão
                $sheet->setCellValue('P' . $row, 1); // Ad. Entreg - valor padrão
                $sheet->setCellValue('Q' . $row, $peso ?? 0); // Peso
                $sheet->setCellValue('R' . $row, 0); // Valor Brut - valor padrão
                $sheet->setCellValue('S' . $row, $record->frete ?? 0); // Frete Líqu
                $sheet->setCellValue('T' . $row, $record->frete ?? 0); // Valor do F

                $row++;
                
                // Liberar memória a cada 100 registros
                if ($row % 100 === 0) {
                    $spreadsheet->garbageCollect();
                }
            }

            // Aplicar formatação de número nas colunas de valores
            $sheet->getStyle('L2:L' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('M2:M' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('N2:N' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('Q2:Q' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('R2:R' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('S2:S' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('T2:T' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

            // Aplicar bordas em todas as células com dados
            $dataRange = 'A1:T' . ($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Auto-ajustar largura das colunas
            foreach (range('A', 'T') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Congelar primeira linha
            $sheet->freezePane('A2');

            // Criar response de download
            $fileName = 'viagens_bugio_export_' . date('Y-m-d_His') . '.xlsx';

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
