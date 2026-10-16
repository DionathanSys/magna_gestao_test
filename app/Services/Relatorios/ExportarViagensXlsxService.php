<?php

namespace App\Services\Relatorios;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Viagem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarViagensXlsxService
{
    private const TEMPLATE_FILE = 'Modelo Importação bugio.xlsx';

    private const HEADERS = [
        'A' => 'Tipo de Fr',
        'B' => 'Transporta',
        'C' => 'Nome Transportador',
        'D' => 'Placa',
        'E' => 'Nota F',
        'F' => 'Nº de viag',
        'G' => 'Destino',
        'H' => 'Local',
        'I' => 'N.Ext.',
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

    public function exportByPeriod(string $cliente, string $dataInicio, string $dataFim): ?StreamedResponse
    {
        $viagens = Viagem::query()
            ->select([
                'id',
                'veiculo_id',
                'numero_viagem',
                'data_competencia',
                'km_pago',
                'total_destinos',
                'cliente',
            ])
            ->with([
                'veiculo:id,placa',
                'cargas.integrado:id,nome,municipio',
                'documentos:id,viagem_id,numero_documento,tipo_documento,valor_total,valor_liquido',
            ])
            ->where('cliente', $cliente)
            ->whereBetween('data_competencia', [$dataInicio, $dataFim])
            ->orderBy('data_competencia')
            ->orderBy('numero_viagem')
            ->get();

        if ($viagens->isEmpty()) {
            return null;
        }

        return $this->export($viagens, sprintf(
            'viagens_%s_%s_a_%s.xlsx',
            str($cliente)->slug('_'),
            Carbon::parse($dataInicio)->format('Y-m-d'),
            Carbon::parse($dataFim)->format('Y-m-d'),
        ));
    }

    public function export(Collection $viagens, ?string $fileName = null): StreamedResponse
    {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $spreadsheet = IOFactory::load($this->templatePath());
            $sheet = $spreadsheet->getSheetByName('Planilha1') ?? $spreadsheet->getActiveSheet();
            $sheet->setTitle('Planilha1');

            $this->clearTemplateData($sheet);
            $this->writeHeaders($sheet);

            foreach ($viagens->values() as $index => $viagem) {
                $this->writeTripRow($sheet, $index + 2, $viagem);
            }

            $sheet->freezePane('A2');
            $fileName ??= 'viagens_'.now()->format('Y-m-d_His').'.xlsx';

            return new StreamedResponse(
                function () use ($spreadsheet): void {
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

    private function templatePath(): string
    {
        $path = base_path(self::TEMPLATE_FILE);

        if (! is_file($path)) {
            throw new \RuntimeException('O modelo de importação não foi encontrado na raiz do projeto.');
        }

        return $path;
    }

    private function clearTemplateData($sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            foreach (array_keys(self::HEADERS) as $column) {
                $cell = $sheet->getCell($column.$row);

                if ($cell->getValue() !== null) {
                    $cell->setValue(null);
                }
            }
        }
    }

    private function writeHeaders($sheet): void
    {
        foreach (self::HEADERS as $column => $header) {
            $sheet->setCellValue($column.'1', $header);
        }
    }

    private function writeTripRow($sheet, int $row, Viagem $viagem): void
    {
        $integrados = $viagem->cargas
            ->pluck('integrado')
            ->filter()
            ->unique('id');
        $documentos = $viagem->documentos ?? collect();
        $valorCtrc = $this->sumDocuments($documentos, fn ($documento): bool => $this->tipoDocumento($documento) !== TipoDocumentoEnum::NFS->value, 'valor_total');
        $valorRecibo = $this->sumDocuments($documentos, fn ($documento): bool => $this->tipoDocumento($documento) === TipoDocumentoEnum::NFS->value, 'valor_total');
        $freteLiquido = $this->sumDocuments($documentos, fn ($documento): bool => $this->tipoDocumento($documento) !== TipoDocumentoEnum::NFS->value, 'valor_liquido');
        $valorFrete = $this->sumDocuments($documentos, fn (): bool => true, 'valor_total');

        $sheet->setCellValue('A'.$row, 150);
        $sheet->setCellValue('B'.$row, 384033);
        $this->setString($sheet, 'C', $row, 'MAGNABOSCO COM E TRANSPORTES LTDA');
        $this->setString($sheet, 'D', $row, $viagem->veiculo?->placa ?? '');
        $this->setString($sheet, 'E', $row, $this->uniqueText($documentos->pluck('numero_documento')) ?: '0');
        $this->setString($sheet, 'F', $row, $this->formatNumeroViagem($viagem->numero_viagem));
        $this->setString($sheet, 'G', $row, $integrados->pluck('nome')->filter()->unique()->implode('; '));
        $this->setString($sheet, 'H', $row, $integrados->pluck('municipio')->filter()->unique()->implode('; '));
        $this->setString($sheet, 'I', $row, 'v');
        $this->setString($sheet, 'J', $row, '');
        $this->setString($sheet, 'K', $row, $viagem->data_competencia
            ? Carbon::parse($viagem->data_competencia)->format('d/m/Y')
            : '');
        $sheet->setCellValue('L'.$row, $valorCtrc);
        $sheet->setCellValue('M'.$row, $valorRecibo);
        $sheet->setCellValue('N'.$row, (float) ($viagem->km_pago ?? 0));
        $sheet->setCellValue('O'.$row, (int) ($viagem->total_destinos ?? 0));
        $sheet->setCellValue('P'.$row, 0);
        $sheet->setCellValue('Q'.$row, 0);
        $sheet->setCellValue('R'.$row, 0);
        $sheet->setCellValue('S'.$row, $freteLiquido);
        $sheet->setCellValue('T'.$row, $valorFrete);
    }

    private function sumDocuments(Collection $documentos, callable $filter, string $column): float
    {
        return (float) $documentos
            ->filter($filter)
            ->sum(fn ($documento): float => (float) ($documento->{$column} ?? 0));
    }

    private function tipoDocumento($documento): ?string
    {
        $tipo = $documento->tipo_documento;

        return $tipo instanceof TipoDocumentoEnum ? $tipo->value : ($tipo ?: null);
    }

    private function formatNumeroViagem(?string $numeroViagem): string
    {
        $numeroViagem = trim((string) $numeroViagem);

        if (preg_match('/^BG-(\d+)$/i', $numeroViagem, $matches)) {
            return str_pad($matches[1], 6, '0', STR_PAD_LEFT);
        }

        return $numeroViagem ?: '0';
    }

    private function setString($sheet, string $column, int $row, string $value): void
    {
        $sheet->setCellValueExplicit($column.$row, $value, DataType::TYPE_STRING);
    }

    private function uniqueText(Collection $values): string
    {
        return $values
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->implode('; ');
    }
}
