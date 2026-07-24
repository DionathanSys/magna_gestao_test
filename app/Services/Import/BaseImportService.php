<?php

namespace App\Services\Import;

use App\Contracts\ExcelImportInterface;
use App\Jobs\FinalizeImportJob;
use App\Jobs\ProcessImportRowJob;
use App\Models\ImportLog;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class BaseImportService
{
    use ServiceResponseTrait;

    protected array $errors = [];

    protected array $warnings = [];

    protected int $processedRows = 0;

    protected int $successRows = 0;

    protected int $errorRows = 0;

    protected ImportLogService $importLogService;

    public function __construct() {}

    public function import(string $filePath, ExcelImportInterface $importer, array $options = []): array
    {
        try {
            $headerRow = max(1, (int) ($options['header_row'] ?? 1));
            $options['import_type'] = $options['import_type'] ?? get_class($importer);

            // Criar log de importação
            $importLog = ImportLogService::createImportLog($filePath, $options);
            $this->importLogService = new ImportLogService($importLog->id);

            // Validar arquivo
            $this->validateFile($filePath);

            // Ler arquivo Excel
            $spreadsheet = IOFactory::load(Storage::disk('public')->path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validar cabeçalho
            $this->validateHeaders($rows[$headerRow - 1] ?? [], $importer);

            // Processar linhas
            $this->processRows($rows, $importer, $importLog->id, $options);

            $this->setSuccess('Importação iniciada.');

            return [
                'import_log_id' => $importLog->id,
            ];

        } catch (\Exception $e) {

            $this->setError('Erro na importação: '.$e->getMessage());

            if (! $this->importLogService) {
                $importLog = ImportLog::where('file_path', Storage::disk('public')->path($filePath))->latest()->first();
                $this->importLogService = new ImportLogService($importLog->id);
            }

            $this->importLogService->failed();

            return [
                'errors' => [$e->getMessage()],
            ];
        }
    }

    protected function validateFile(string $filePath): void
    {
        if (! Storage::disk('public')->exists($filePath)) {
            throw new \InvalidArgumentException('Arquivo não encontrado.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (! in_array(strtolower($extension), ['xlsx', 'xls', 'csv'])) {
            throw new \InvalidArgumentException('Formato de arquivo não suportado.');
        }
    }

    protected function validateHeaders(array $headers, ExcelImportInterface $importer): void
    {
        $requiredColumns = $importer->getRequiredColumns();
        $missingColumns = [];

        Log::debug('Validando cabeçalho do arquivo de importação.', ['headers' => $headers, 'required_columns' => $requiredColumns]);

        foreach ($headers as $key => $value) {
            $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
            $headers[$key] = $value;
        }

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $headers)) {

                Log::warning("Coluna obrigatória ausente: {$column}", [
                    'headers' => $headers,
                    'required_columns' => $requiredColumns,
                ]);

                $missingColumns[] = $column;
            }
        }

        Log::debug('Colunas obrigatórias não encontradas no cabeçalho.', ['missing_columns' => $missingColumns]);

        if (! empty($missingColumns)) {
            throw new \InvalidArgumentException(
                'Colunas obrigatórias não encontradas: '.implode(', ', $missingColumns)
            );
        }

        Log::info('Cabeçalho validado com sucesso.');
    }

    protected function processRows(array $rows, ExcelImportInterface $importer, int $importLogId, array $options, array $additionalData = []): void
    {
        $headerRow = max(1, (int) ($options['header_row'] ?? 1));
        $headers = $rows[$headerRow - 1] ?? [];
        $rows = array_slice($rows, $headerRow);
        $rows = array_values(array_filter($rows, function (array $row): bool {
            return count(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== '')) > 0;
        }));

        // Remove caracteres especiais dos cabeçalhos
        foreach ($headers as $key => $value) {
            $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
            $headers[$key] = $value;
        }

        if (method_exists($importer, 'shouldSkipRow')) {
            $rows = array_values(array_filter($rows, function (array $row) use ($headers, $importer): bool {
                $rowData = array_combine($headers, $row);

                return ! $importer->shouldSkipRow($rowData);
            }));
        }

        $batchSize = $options['batch_size'] ?? 50;

        $batches = array_chunk($rows, $batchSize);
        $totalBatches = count($batches);

        Log::debug('Iniciando processamento de importação em '.($options['use_queue'] ? 'fila' : 'síncrono').'.', [
            'total_rows' => count($rows),
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
            'import_log_id' => $importLogId,

        ]);

        $this->importLogService->update([
            'total_rows' => count($rows),
            'total_batches' => $totalBatches,
        ]);

        Log::info('Processando '.count($rows)." linhas em {$totalBatches} lotes de tamanho {$batchSize}.");

        $startRowNumber = $headerRow + 1;

        foreach ($batches as $batchIndex => $batch) {
            ProcessImportRowJob::dispatch(
                $batch,
                $headers,
                get_class($importer),
                $importLogId,
                $startRowNumber + ($batchIndex * $batchSize)
            );
        }

        FinalizeImportJob::dispatch($importLogId, get_class($importer));

        Log::info('Todos os lotes foram enfileirados para import_log_id: '.$importLogId);
    }
}
