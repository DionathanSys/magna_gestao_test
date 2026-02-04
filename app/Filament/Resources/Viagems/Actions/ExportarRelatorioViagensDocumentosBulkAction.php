<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models\Viagem;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarRelatorioViagensDocumentosBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('exportar-relatorio-viagens-documentos')
            ->label('Exportar Relatório Completo')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Exportar Relatório de Viagens e Documentos')
            ->modalDescription(fn (Collection $records) => 
                'Você está prestes a exportar ' . $records->count() . ' viagen(s) com seus documentos para Excel. O relatório conterá 2 planilhas: uma com as viagens e outra com os documentos vinculados.'
            )
            ->modalSubmitActionLabel('Exportar')
            ->action(function (Collection $records) {
                // Limitar exportação para evitar problemas de memória
                if ($records->count() > 1000) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Muitos registros')
                        ->body('Por favor, selecione no máximo 1000 registros para exportar.')
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
            
            // ====== PLANILHA 1: VIAGENS ======
            $viagensSheet = $spreadsheet->getActiveSheet();
            $viagensSheet->setTitle('Viagens');

            // Definir cabeçalhos das viagens
            $viagensHeaders = [
                'A' => 'ID',
                'B' => 'Nº Viagem',
                'C' => 'Doc. Transporte',
                'D' => 'Placa',
                'E' => 'Data Competência',
                'F' => 'Km Pago',
            ];

            // Escrever cabeçalhos
            $row = 1;
            foreach ($viagensHeaders as $col => $header) {
                $viagensSheet->setCellValue($col . $row, $header);
            }

            // Estilizar cabeçalho
            $headerRange = 'A1:F1';
            static::styleHeader($viagensSheet, $headerRange);

            // Ajustar largura das colunas
            $viagensSheet->getColumnDimension('A')->setWidth(10);
            $viagensSheet->getColumnDimension('B')->setWidth(20);
            $viagensSheet->getColumnDimension('C')->setWidth(25);
            $viagensSheet->getColumnDimension('D')->setWidth(15);
            $viagensSheet->getColumnDimension('E')->setWidth(18);
            $viagensSheet->getColumnDimension('F')->setWidth(15);

            // Carregar viagens com relacionamentos necessários
            $viagens = Viagem::whereIn('id', $records->pluck('id'))
                ->with(['veiculo'])
                ->get();

            // Escrever dados das viagens
            $row = 2;
            foreach ($viagens as $viagem) {
                $viagensSheet->setCellValue('A' . $row, $viagem->id);
                $viagensSheet->setCellValue('B' . $row, $viagem->numero_viagem ?? '');
                $viagensSheet->setCellValue('C' . $row, $viagem->documento_transporte ?? '');
                $viagensSheet->setCellValue('D' . $row, $viagem->veiculo?->placa ?? '');
                $viagensSheet->setCellValue('E' . $row, $viagem->data_competencia ? \Carbon\Carbon::parse($viagem->data_competencia)->format('d/m/Y') : '');
                $viagensSheet->setCellValue('F' . $row, $viagem->km_pago ?? '');
                
                $row++;
            }

            // Aplicar bordas
            if ($row > 2) {
                static::applyBorders($viagensSheet, 'A1:F' . ($row - 1));
            }

            // ====== PLANILHA 2: DOCUMENTOS ======
            $documentosSheet = $spreadsheet->createSheet();
            $documentosSheet->setTitle('Documentos');

            // Definir cabeçalhos dos documentos
            $documentosHeaders = [
                'A' => 'ID',
                'B' => 'Viagem ID',
                'C' => 'Nº Documento',
                'D' => 'Doc. Transporte',
                'E' => 'Data Emissão',
                'F' => 'Valor Total',
                'G' => 'Valor ICMS',
                'H' => 'Valor Líquido',
                'I' => 'Parceiro Origem',
                'J' => 'Parceiro Destino',
                'K' => 'Tipo Documento',
            ];

            // Escrever cabeçalhos
            $row = 1;
            foreach ($documentosHeaders as $col => $header) {
                $documentosSheet->setCellValue($col . $row, $header);
            }

            // Estilizar cabeçalho
            $headerRange = 'A1:K1';
            static::styleHeader($documentosSheet, $headerRange);

            // Ajustar largura das colunas
            $documentosSheet->getColumnDimension('A')->setWidth(10);
            $documentosSheet->getColumnDimension('B')->setWidth(12);
            $documentosSheet->getColumnDimension('C')->setWidth(20);
            $documentosSheet->getColumnDimension('D')->setWidth(25);
            $documentosSheet->getColumnDimension('E')->setWidth(18);
            $documentosSheet->getColumnDimension('F')->setWidth(15);
            $documentosSheet->getColumnDimension('G')->setWidth(15);
            $documentosSheet->getColumnDimension('H')->setWidth(15);
            $documentosSheet->getColumnDimension('I')->setWidth(35);
            $documentosSheet->getColumnDimension('J')->setWidth(35);
            $documentosSheet->getColumnDimension('K')->setWidth(20);

            // Carregar documentos das viagens selecionadas
            $documentos = \App\Models\DocumentoFrete::whereIn('viagem_id', $viagens->pluck('id'))
                ->orderBy('viagem_id')
                ->orderBy('id')
                ->get();

            // Escrever dados dos documentos
            $row = 2;
            foreach ($documentos as $documento) {
                $documentosSheet->setCellValue('A' . $row, $documento->id);
                $documentosSheet->setCellValue('B' . $row, $documento->viagem_id ?? '');
                $documentosSheet->setCellValue('C' . $row, $documento->numero_documento ?? '');
                $documentosSheet->setCellValue('D' . $row, $documento->documento_transporte ?? '');
                $documentosSheet->setCellValue('E' . $row, $documento->data_emissao ? \Carbon\Carbon::parse($documento->data_emissao)->format('d/m/Y') : '');
                
                // Formatar valores monetários
                $valorTotal = static::formatMoneyValue($documento->valor_total);
                $valorIcms = static::formatMoneyValue($documento->valor_icms);
                $valorLiquido = static::formatMoneyValue($documento->valor_liquido);
                
                $documentosSheet->setCellValue('F' . $row, $valorTotal);
                $documentosSheet->setCellValue('G' . $row, $valorIcms);
                $documentosSheet->setCellValue('H' . $row, $valorLiquido);
                
                $documentosSheet->setCellValue('I' . $row, $documento->parceiro_origem ?? '');
                $documentosSheet->setCellValue('J' . $row, $documento->parceiro_destino ?? '');
                $documentosSheet->setCellValue('K' . $row, $documento->tipo_documento?->value ?? '');
                
                $row++;
            }

            // Aplicar bordas
            if ($row > 2) {
                static::applyBorders($documentosSheet, 'A1:K' . ($row - 1));
            }

            // Definir a primeira planilha como ativa
            $spreadsheet->setActiveSheetIndex(0);

            // Restaurar limite de memória original
            ini_set('memory_limit', $originalMemoryLimit);

            // Retornar resposta de download
            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 'relatorio_viagens_documentos_' . date('Y-m-d_His') . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            // Restaurar limite de memória em caso de erro
            ini_set('memory_limit', $originalMemoryLimit);
            
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Erro ao exportar')
                ->body('Ocorreu um erro ao gerar o arquivo Excel: ' . $e->getMessage())
                ->send();
                
            throw $e;
        }
    }

    protected static function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected static function applyBorders($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    protected static function formatMoneyValue($value): string
    {
        if (is_null($value)) {
            return '0,00';
        }
        
        // Se for um objeto Brick\Money\Money
        if (is_object($value) && method_exists($value, 'getAmount')) {
            $amount = $value->getAmount()->toFloat();
            return number_format($amount, 2, ',', '.');
        }
        
        // Se for numérico
        if (is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.');
        }
        
        return '0,00';
    }
}
