<?php

namespace App\Services\Relatorios;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\ViagemBugio;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarViagensBugioXlsxService
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

    public function exportByPeriod(string $dataInicio, string $dataFim): ?StreamedResponse
    {
        $records = ViagemBugio::query()
            ->select([
                'id',
                'veiculo_id',
                'destinos',
                'nro_documento',
                'numero_sequencial',
                'info_adicionais',
                'data_competencia',
                'km_pago',
                'frete',
                'status',
            ])
            ->with('veiculo:id,placa')
            ->whereBetween('data_competencia', [$dataInicio, $dataFim])
            ->orderBy('numero_sequencial')
            ->orderBy('id')
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        return $this->export($records, sprintf(
            'viagens_bugio_%s_a_%s.xlsx',
            Carbon::parse($dataInicio)->format('Y-m-d'),
            Carbon::parse($dataFim)->format('Y-m-d'),
        ));
    }

    public function export(Collection $records, ?string $fileName = null): StreamedResponse
    {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $groups = $records
                ->groupBy(fn (ViagemBugio $record): string => $record->numero_sequencial
                    ? (string) $record->numero_sequencial
                    : 'sem-numero-'.$record->id)
                ->sortBy(fn (Collection $group, $key): int => is_numeric($key) ? (int) $key : PHP_INT_MAX);

            $integrados = $this->loadIntegrados($records);
            $spreadsheet = IOFactory::load($this->templatePath());
            $sheet = $spreadsheet->getSheetByName('Planilha1') ?? $spreadsheet->getActiveSheet();
            $sheet->setTitle('Planilha1');

            $this->clearTemplateData($sheet);
            $this->writeHeaders($sheet);

            $row = 2;
            foreach ($groups as $group) {
                $this->writeTripRow($sheet, $row, $group, $integrados);
                $row++;
            }

            $sheet->freezePane('A2');
            $fileName ??= 'viagens_bugio_'.now()->format('Y-m-d_His').'.xlsx';

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
            throw new \RuntimeException('O modelo de importação Bugio não foi encontrado na raiz do projeto.');
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

    private function loadIntegrados(Collection $records): Collection
    {
        $integradoIds = $records
            ->flatMap(fn (ViagemBugio $record): array => $this->destinos($record->destinos))
            ->pluck('integrado_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($integradoIds->isEmpty()) {
            return collect();
        }

        return Integrado::query()
            ->whereIn('id', $integradoIds)
            ->get(['id', 'nome', 'municipio'])
            ->keyBy('id');
    }

    private function writeTripRow($sheet, int $row, Collection $records, Collection $integrados): void
    {
        /** @var ViagemBugio $representative */
        $representative = $records->first();
        $destinos = $records
            ->flatMap(fn (ViagemBugio $record): array => $this->destinos($record->destinos))
            ->values();

        $destinationNames = $destinos
            ->map(function (array $destination) use ($integrados): ?string {
                $integrado = $integrados->get((int) ($destination['integrado_id'] ?? 0));

                return $destination['integrado_nome'] ?? $integrado?->nome;
            });

        $municipios = $destinos
            ->map(function (array $destination) use ($integrados): ?string {
                $integrado = $integrados->get((int) ($destination['integrado_id'] ?? 0));

                return $destination['municipio'] ?? $integrado?->municipio;
            });

        $frete = $records->sum(fn (ViagemBugio $record): float => (float) ($record->frete ?? 0));
        $valorCtrc = $records
            ->filter(fn (ViagemBugio $record): bool => $this->tipoDocumento($record) !== TipoDocumentoEnum::NFS->value)
            ->sum(fn (ViagemBugio $record): float => (float) ($record->frete ?? 0));
        $valorRecibo = $records
            ->filter(fn (ViagemBugio $record): bool => $this->tipoDocumento($record) === TipoDocumentoEnum::NFS->value)
            ->sum(fn (ViagemBugio $record): float => (float) ($record->frete ?? 0));
        $peso = $records->sum(fn (ViagemBugio $record): float => (float) ($this->informacoes($record)['peso'] ?? 0));
        $numeroSequencial = $representative->numero_sequencial
            ? str_pad((string) $representative->numero_sequencial, 6, '0', STR_PAD_LEFT)
            : '0';
        $documentos = $this->uniqueText($records->pluck('nro_documento')) ?: '0';
        $status = $this->firstText($records->pluck('status'));

        $sheet->setCellValue('A'.$row, 150);
        $sheet->setCellValue('B'.$row, 384033);
        $this->setString($sheet, 'C', $row, 'MAGNABOSCO COM E TRANSPORTES LTDA');
        $this->setString($sheet, 'D', $row, $representative->veiculo?->placa ?? '');
        $this->setString($sheet, 'E', $row, $documentos);
        $this->setString($sheet, 'F', $row, $numeroSequencial);
        $this->setString($sheet, 'G', $row, $this->uniqueText($destinationNames));
        $this->setString($sheet, 'H', $row, $this->uniqueText($municipios));
        $this->setString($sheet, 'I', $row, 'v');
        $this->setString($sheet, 'J', $row, $status);
        $this->setString($sheet, 'K', $row, $representative->data_competencia
            ? Carbon::parse($representative->data_competencia)->format('d/m/Y')
            : '');
        $sheet->setCellValue('L'.$row, $valorCtrc);
        $sheet->setCellValue('M'.$row, max(0, $valorRecibo));
        $sheet->setCellValue('N'.$row, $records->sum(fn (ViagemBugio $record): float => (float) ($record->km_pago ?? 0)));
        $sheet->setCellValue('O'.$row, 1);
        $sheet->setCellValue('P'.$row, 0);
        $sheet->setCellValue('Q'.$row, $peso);
        $sheet->setCellValue('R'.$row, 0);
        $sheet->setCellValue('S'.$row, $frete);
        $sheet->setCellValue('T'.$row, $frete);
    }

    private function setString($sheet, string $column, int $row, string $value): void
    {
        $sheet->setCellValueExplicit($column.$row, $value, DataType::TYPE_STRING);
    }

    private function destinos(mixed $destinos): array
    {
        if (is_string($destinos)) {
            $destinos = json_decode($destinos, true);
        }

        if (! is_array($destinos) || $destinos === []) {
            return [];
        }

        if (array_key_exists('integrado_id', $destinos) || array_key_exists('integrado_nome', $destinos)) {
            return [$destinos];
        }

        return array_values(array_filter($destinos, is_array(...)));
    }

    private function informacoes(ViagemBugio $record): array
    {
        $info = $record->info_adicionais;

        if (is_string($info)) {
            $info = json_decode($info, true);
        }

        return is_array($info) ? $info : [];
    }

    private function tipoDocumento(ViagemBugio $record): ?string
    {
        return $this->informacoes($record)['tipo_documento'] ?? null;
    }

    private function uniqueText(Collection $values): string
    {
        return $values
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->implode('; ');
    }

    private function firstText(Collection $values): string
    {
        return $values
            ->map(fn ($value): string => trim((string) $value))
            ->first(fn (string $value): bool => $value !== '') ?? '';
    }
}
