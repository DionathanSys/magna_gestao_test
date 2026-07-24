<?php

namespace App\Services\Import\Importers;

use App\Contracts\ExcelImportInterface;
use App\Models\ManutencaoLancamento;
use App\Models\Veiculo;
use App\Services\Manutencao\ManutencaoImportSyncService;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ManutencaoImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;

    private ?int $importLogId = null;

    public function __construct(
        private ManutencaoImportSyncService $syncService,
    ) {}

    public function setImportContext(int $importLogId): void
    {
        $this->importLogId = $importLogId;
    }

    public function shouldSkipRow(array $row): bool
    {
        return blank($row['TipoManuteno'] ?? null)
            && blank($row['Placa'] ?? null)
            && blank($row['Nrnico'] ?? null)
            && blank($row['NrOSNF'] ?? null)
            && blank($row['Produto'] ?? null);
    }

    public function getRequiredColumns(): array
    {
        return [
            'TipoManuteno',
            'DtNeg',
            'Placa',
            'CdProduto',
            'Produto',
            'Qtd',
            'Origem',
            'VlrTotal',
            'VlrUnitrio',
            'Sequncia',
            'CdVeculo',
            'NrOSNF',
            'Nrnico',
            'Parceiro',
            'GrupoProduto',
            'UN',
        ];
    }

    public function getOptionalColumns(): array
    {
        return [
            'CdLocal',
            'LocalEstoque',
        ];
    }

    public function validate(array $row, int $rowNumber): array
    {
        $placa = $this->normalizeString($row['Placa'] ?? null, true);

        $validator = Validator::make([
            'tipo_manutencao' => $row['TipoManuteno'] ?? null,
            'data_negociacao' => $row['DtNeg'] ?? null,
            'placa' => $placa,
            'valor_total' => $row['VlrTotal'] ?? null,
            'sequencia' => $row['Sequncia'] ?? null,
            'nr_unico' => $row['Nrnico'] ?? null,
        ], [
            'tipo_manutencao' => 'required|string',
            'data_negociacao' => 'required',
            'placa' => 'required|string|exists:veiculos,placa',
            'valor_total' => 'required',
            'sequencia' => 'required',
            'nr_unico' => 'required',
        ], [
            'tipo_manutencao.required' => "O campo Tipo Manutenção é obrigatório na linha {$rowNumber}.",
            'data_negociacao.required' => "O campo Dt. Neg. é obrigatório na linha {$rowNumber}.",
            'placa.required' => "A placa é obrigatória na linha {$rowNumber}.",
            'placa.exists' => "A placa '{$placa}' não foi encontrada na linha {$rowNumber}.",
            'valor_total.required' => "O valor total é obrigatório na linha {$rowNumber}.",
            'sequencia.required' => "A sequência é obrigatória na linha {$rowNumber}.",
            'nr_unico.required' => "O Nr. Único é obrigatório na linha {$rowNumber}.",
        ]);

        $errors = $validator->fails() ? $validator->errors()->all() : [];

        if (! empty($row['DtNeg'])) {
            try {
                Carbon::createFromFormat('d/m/Y', (string) $row['DtNeg']);
            } catch (\Throwable) {
                $errors[] = "A data de negociação da linha {$rowNumber} deve estar no formato dd/mm/aaaa.";
            }
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        $placa = $this->normalizeString($row['Placa'] ?? null, true);
        $veiculoId = Veiculo::query()->where('placa', $placa)->value('id');
        $nrUnico = $this->normalizeIdentifier($row['Nrnico'] ?? null);
        $sequencia = $this->normalizeIdentifier($row['Sequncia'] ?? null);

        return [
            'sync_key' => $nrUnico.'-'.$sequencia,
            'tipo_manutencao' => $this->normalizeString($row['TipoManuteno'] ?? null),
            'data_negociacao' => Carbon::createFromFormat('d/m/Y', (string) $row['DtNeg'])->toDateString(),
            'veiculo_id' => $veiculoId,
            'placa' => $placa,
            'codigo_produto' => $this->normalizeIdentifier($row['CdProduto'] ?? null),
            'produto' => $this->normalizeString($row['Produto'] ?? null),
            'quantidade' => $this->toDecimal($row['Qtd'] ?? null),
            'origem' => $this->normalizeString($row['Origem'] ?? null),
            'valor_total_centavos' => $this->toCents($row['VlrTotal'] ?? null),
            'valor_unitario_centavos' => $this->toCents($row['VlrUnitrio'] ?? null),
            'sequencia' => $sequencia,
            'nr_os_nf' => $this->normalizeIdentifier($row['NrOSNF'] ?? null),
            'nr_unico' => $nrUnico,
            'parceiro' => $this->normalizeString($row['Parceiro'] ?? null) ?: 'Almoxarifado',
            'grupo_produto' => $this->normalizeString($row['GrupoProduto'] ?? null),
            'unidade' => $this->normalizeString($row['UN'] ?? null),
            'codigo_veiculo_erp' => $this->normalizeIdentifier($row['CdVeculo'] ?? null),
            'codigo_local' => $this->normalizeIdentifier($row['CdLocal'] ?? null),
            'local_estoque' => $this->normalizeString($row['LocalEstoque'] ?? null),
        ];
    }

    public function process(array $data, int $rowNumber): ?ManutencaoLancamento
    {
        if ($this->shouldSkipRow($data)) {
            return null;
        }

        if ($this->importLogId === null) {
            $this->setError('Contexto de importação não definido para manutenção.');

            return null;
        }

        $errors = $this->validate($data, $rowNumber);

        if (! empty($errors)) {
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);

            return null;
        }

        $transformedData = $this->transform($data);
        $lancamento = $this->syncService->upsert($transformedData, $this->importLogId);

        if ($this->syncService->hasError()) {
            $this->setError("Erro na linha {$rowNumber}.", $this->syncService->getErrors());

            return null;
        }

        return $lancamento;
    }

    public function finalizeImport(int $importLogId): void
    {
        $this->syncService->finalizeImport($importLogId);
    }

    private function normalizeString(mixed $value, bool $uppercase = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return $uppercase ? Str::upper($normalized) : $normalized;
    }

    private function normalizeIdentifier(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $floatValue = (float) $value;
            $integerValue = (int) $floatValue;

            if ((float) $integerValue === $floatValue) {
                return (string) $integerValue;
            }

            return rtrim(rtrim(number_format($floatValue, 4, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    private function toDecimal(mixed $value): float
    {
        $normalized = $this->normalizeNumeric($value);

        return is_numeric($normalized) ? round((float) $normalized, 4) : 0.0;
    }

    private function toCents(mixed $value): int
    {
        $normalized = $this->normalizeNumeric($value);

        return is_numeric($normalized) ? (int) round(((float) $normalized) * 100) : 0;
    }

    private function normalizeNumeric(mixed $value): string
    {
        if ($value === null) {
            return '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '0';
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }

            return $normalized;
        }

        if (str_contains($normalized, ',')) {
            return str_replace(',', '.', $normalized);
        }

        return $normalized;
    }
}
