<?php

namespace App\Services\Import;

use App\{Models, Enum, Services};
use App\Contracts\ExcelImportInterface;
use App\Jobs\FinalizeImportJob;
use App\Jobs\ProcessImportRowJob;
use App\Models\ImportLog;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\{Auth, DB, Log, Storage};
use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class BaseImportService
{
    use ServiceResponseTrait;

    protected array $errors = [];
    protected array $warnings = [];
    protected int $processedRows = 0;
    protected int $successRows = 0;
    protected int $errorRows = 0;
    protected Services\Import\ImportLogService $importLogService;

    public function __construct()
    {
    }

    public function import(string $filePath, ExcelImportInterface $importer, array $options = []): array
    {
        try {

            // Criar log de importação
            $importLog = Services\Import\ImportLogService::createImportLog($filePath, $options);
            $this->importLogService = new Services\Import\ImportLogService($importLog->id);

            // Validar arquivo
            $this->validateFile($filePath);

            // Ler arquivo Excel
            $spreadsheet = IOFactory::load(Storage::disk('public')->path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validar cabeçalho
            $this->validateHeaders($rows[0] ?? [], $importer);

            // Processar linhas
            $this->processRows($rows, $importer, $importLog->id, $options);

            $this->setSuccess("Importação iniciada.");

            return [
                'import_log_id' => $importLog->id,
            ];

        } catch (\Exception $e) {

            $this->setError("Erro na importação: " . $e->getMessage());

            if (!$this->importLogService) {
                $importLog = ImportLog::where('file_path', Storage::disk('public')->path($filePath))->latest()->first();
                $this->importLogService = new Services\Import\ImportLogService($importLog->id);
            }

            $this->importLogService->failed();

            return [
                'errors' => [$e->getMessage()],
            ];
        }
    }

    protected function validateFile(string $filePath): void
    {
        if (!Storage::disk('public')->exists($filePath)) {
            throw new \InvalidArgumentException('Arquivo não encontrado.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xlsx', 'xls', 'csv'])) {
            throw new \InvalidArgumentException('Formato de arquivo não suportado.');
        }
    }

    protected function validateHeaders(array $headers, ExcelImportInterface $importer): void
    {
        $requiredColumns = $importer->getRequiredColumns();
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            throw new \InvalidArgumentException(
                'Colunas obrigatórias não encontradas: ' . implode(', ', $missingColumns)
            );
        }

    }

    protected function processRows(array $rows, ExcelImportInterface $importer, int $importLogId, array $options): void
    {
        $headers = array_shift($rows); // Remove cabeçalho
        $batchSize = $options['batch_size'] ?? 50;

        $batches = array_chunk($rows, $batchSize);
        $totalBatches = count($batches);

        Log::debug("Iniciando processamento de importação em " . ($options['use_queue'] ? 'fila' : 'síncrono') . ".", [
            'rows' => $rows,
            'total_rows' => count($rows),
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
            'import_log_id' => $importLogId,

        ]);
        
        $this->importLogService->update([
            'total_rows'    => count($rows),
            'total_batches' => $totalBatches,
        ]);

        Log::info("Processando " . count($rows) . " linhas em {$totalBatches} lotes de tamanho {$batchSize}.");

        foreach ($batches as $batch) {
            ProcessImportRowJob::dispatch($batch, $headers, get_class($importer), $importLogId);
        }

        FinalizeImportJob::dispatch($importLogId);
    }

}

