<?php

namespace App\Services\Relatorios;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TratadorRelatorioBrfService
{
    private array $columnMap = [];

    private array $columnNames = [
        'Transportador',
        'Doc. Transporte',
        'NºCustoFrete',
        'Frete Em',
        'Nr. Placa',
        'Nº NF',
        'Nome Fornecedor',
        'LocDest.',
        'Quantidade',
        'KM Realizado',
        'ValorFrete',
        'NroConhecimento',
        'NF Serviço',
        'Tp. Frete',
        'Entregas',
    ];

    private array $stats = [
        'total_rows_read' => 0,
        'total_trips' => 0,
        'single_delivery_trips' => 0,
        'multi_delivery_trips' => 0,
        'total_output_rows' => 0,
        'rateado_rows' => 0,
        'divergencias' => [],
    ];

    public function process(string $filePath): array
    {
        $fullPath = Storage::disk('public')->path($filePath);

        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = $this->readHeaders($worksheet);
            $this->mapColumns($headers);

            $groups = $this->readRows($worksheet);

            $outputRows = $this->processGroups($groups);

            $outputFilename = $this->generateExcel($outputRows);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return [
                'output_path' => $outputFilename,
                'stats' => $this->stats,
            ];
        } catch (\Exception $e) {
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }

            throw $e;
        }
    }

    private function readHeaders($worksheet): array
    {
        $row = $worksheet->getRowIterator(1, 1)->current();
        $headers = [];
        foreach ($row->getCellIterator() as $cell) {
            $headers[] = trim((string) $cell->getValue());
        }

        return $headers;
    }

    private function mapColumns(array $headers): void
    {
        $normalizedExpected = [];
        foreach ($this->columnNames as $col) {
            $normalizedExpected[$this->normalize($col)] = $col;
        }

        foreach ($this->columnAliases() as $col => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedExpected[$this->normalize($alias)] = $col;
            }
        }

        foreach ($headers as $index => $header) {
            $normalizedHeader = $this->normalize($header);
            if (isset($normalizedExpected[$normalizedHeader])) {
                $this->columnMap[$normalizedExpected[$normalizedHeader]] = $index;
            }
        }

        $missing = array_diff($this->columnNames, array_keys($this->columnMap));

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Colunas obrigatórias não encontradas no arquivo: '.implode(', ', $missing)
            );
        }
    }

    private function columnAliases(): array
    {
        return [
            'NºCustoFrete' => [
                'NºCustFrete',
                'Nº Custo Frete',
                'Nº Cust Frete',
                'NoCustoFrete',
                'NoCustFrete',
            ],
            'Frete Em' => [
                'Frete em',
            ],
            'Tp. Frete' => [
                'Tp.Frete',
                'Tp Frete',
                'Tipo Frete',
            ],
        ];
    }

    private function normalize(string $name): string
    {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = preg_replace('/[^\w\s]/u', '', $name);

        return strtolower(trim($name));
    }

    private function readRows($worksheet): array
    {
        $groups = [];

        foreach ($worksheet->getRowIterator(2) as $row) {
            $this->stats['total_rows_read']++;

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            $nonEmpty = array_filter($rowData, fn ($v) => $v !== null && trim((string) $v) !== '');
            if ($nonEmpty === []) {
                continue;
            }

            $assocRow = [];
            foreach ($this->columnMap as $colName => $index) {
                $assocRow[$colName] = $rowData[$index] ?? null;
            }

            $docKey = trim((string) ($assocRow['Doc. Transporte'] ?? ''));
            if ($docKey === '') {
                continue;
            }

            $groups[$docKey][] = $assocRow;
        }

        return $groups;
    }

    private function processGroups(array $groups): array
    {
        $outputRows = [];

        foreach ($groups as $docTransporte => $rows) {
            $this->stats['total_trips']++;

            if (count($rows) === 1) {
                $row = $rows[0];
                $row['Rateado'] = 'Não';
                $row['Observação Rateio'] = '';
                $outputRows[] = $row;
                $this->stats['single_delivery_trips']++;
                $this->stats['total_output_rows']++;
            } else {
                $summaryRows = $this->findSummaryRows($rows);
                $header = $this->consolidateSummaryRows($summaryRows !== [] ? $summaryRows : [$rows[0]]);
                $deliveries = array_values(array_filter(
                    $rows,
                    fn (array $row): bool => trim((string) ($row['NºCustoFrete'] ?? '')) === ''
                        && trim((string) ($row['Nome Fornecedor'] ?? '')) !== ''
                ));

                $deliveryCount = count($deliveries);
                $entregasInformadas = (int) ($header['Entregas'] ?? 0);
                if ($entregasInformadas > 0 && $entregasInformadas !== $deliveryCount) {
                    $this->stats['divergencias'][] =
                        "Doc. Transporte {$docTransporte}: informa {$entregasInformadas} entrega(s), mas possui {$deliveryCount} destino(s) com Nome Fornecedor";
                }

                if ($deliveryCount === 0) {
                    $this->stats['divergencias'][] =
                        "Doc. Transporte {$docTransporte}: nenhuma entrega encontrada com Nome Fornecedor preenchido";

                    continue;
                }

                if (count($summaryRows) > 1) {
                    $this->stats['divergencias'][] =
                        "Doc. Transporte {$docTransporte}: totais somados de ".count($summaryRows).' linhas sumarizadoras';
                }

                $this->stats['multi_delivery_trips']++;

                foreach ($deliveries as $delivery) {
                    $outputRows[] = $this->mergeRows($header, $delivery, $deliveryCount);
                    $this->stats['rateado_rows']++;
                    $this->stats['total_output_rows']++;
                }
            }
        }

        return $outputRows;
    }

    private function findSummaryRows(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => trim((string) ($row['NºCustoFrete'] ?? '')) !== ''
        ));
    }

    private function consolidateSummaryRows(array $rows): array
    {
        $summary = $rows[0];

        foreach (['Quantidade', 'KM Realizado', 'ValorFrete'] as $field) {
            $summary[$field] = array_sum(array_map(
                fn (array $row): float => $this->toNumeric($row[$field] ?? 0),
                $rows
            ));
        }

        $summary['NºCustoFrete'] = collect($rows)
            ->pluck('NºCustoFrete')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->implode(', ');

        return $summary;
    }

    private function mergeRows(array $header, array $delivery, int $totalDeliveries): array
    {
        $row = [];

        $fromHeader = ['Transportador', 'Doc. Transporte', 'NºCustoFrete', 'Frete Em', 'Nr. Placa', 'Tp. Frete', 'Entregas'];
        foreach ($fromHeader as $field) {
            $row[$field] = $header[$field] ?? null;
        }
        $row['Entregas'] = $totalDeliveries;

        $fromDelivery = ['Nº NF', 'Nome Fornecedor', 'LocDest.', 'NroConhecimento', 'NF Serviço'];
        foreach ($fromDelivery as $field) {
            $row[$field] = $delivery[$field] ?? null;
        }

        $totals = [
            'Quantidade' => $this->toNumeric($header['Quantidade'] ?? 0),
            'KM Realizado' => $this->toNumeric($header['KM Realizado'] ?? 0),
            'ValorFrete' => $this->toNumeric($header['ValorFrete'] ?? 0),
        ];

        $row['Quantidade'] = $totalDeliveries > 0 ? round($totals['Quantidade'] / $totalDeliveries, 3) : 0;
        $row['KM Realizado'] = $totalDeliveries > 0 ? round($totals['KM Realizado'] / $totalDeliveries, 2) : 0;
        $row['ValorFrete'] = $totalDeliveries > 0 ? round($totals['ValorFrete'] / $totalDeliveries, 2) : 0;

        $row['Rateado'] = 'Sim';
        $row['Observação Rateio'] = "Valor rateado entre {$totalDeliveries} entregas";

        return $row;
    }

    private function toNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = (string) $value;
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private function generateExcel(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $outputColumns = array_merge($this->columnNames, ['Rateado', 'Observação Rateio']);

        foreach ($outputColumns as $colIndex => $colName) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter.'1', $colName);
            $sheet->getStyle($colLetter.'1')->getFont()->setBold(true);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            foreach ($outputColumns as $colIndex => $colName) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                $value = $row[$colName] ?? null;
                if (is_float($value)) {
                    $sheet->setCellValue($colLetter.$rowNum, $value);
                } else {
                    $sheet->setCellValueExplicit(
                        $colLetter.$rowNum,
                        $value,
                        DataType::TYPE_STRING
                    );
                }
            }
            $rowNum++;
        }

        foreach ($outputColumns as $colIndex => $colName) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $tempDir = Storage::disk('public')->path('temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $filename = 'temp/relatorio_brf_tratado_'.now()->format('Ymd_His').'_'.Str::random(6).'.xlsx';
        $fullPath = Storage::disk('public')->path($filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $filename;
    }
}
