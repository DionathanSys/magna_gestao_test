<?php

namespace App\Services\Import;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
use App\Jobs\FinalizeImportJob;
use App\Jobs\ProcessImportRowJob;
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
    protected Models\ImportLog $importLog;

    public function import(string $filePath, ExcelImportInterface $importer, array $options = []): array
    {
        try {

            // Criar log de importação
            $this->importLog = $this->createImportLog($filePath, $options);

            // Validar arquivo
            $this->validateFile($filePath);

            // Ler arquivo Excel
            $spreadsheet = IOFactory::load(Storage::disk('public')->path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validar cabeçalho
            $this->validateHeaders($rows[0] ?? [], $importer);

            // Processar linhas
            $this->processRows($rows, $importer, $this->importLog, $options);

            // Finalizar log
            $this->finalizeImportLog($this->importLog);

            $this->setSuccess("Importação concluída. {$this->successRows} registros processados com sucesso.");

            return [
                'import_log_id' => $this->importLog->id,
                'total_rows' => $this->processedRows,
                'success_rows' => $this->successRows,
                'error_rows' => $this->errorRows,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        } catch (\Exception $e) {

            $this->setError("Erro na importação: " . $e->getMessage());

            // $this->finalizeImportLog($this->importLog);

            return [
                'errors' => [$e->getMessage()],
                'total_rows' => $this->processedRows,
                'success_rows' => 0,
                'error_rows' => $this->processedRows,
            ];
        }
    }

    protected function createImportLog(string $filePath, array $options): Models\ImportLog
    {

        $log = Models\ImportLog::create([
            'import_description' => $options['descricao'] ?? 'Importação de dados',
            'file_name' => basename($filePath),
            'file_path' => Storage::disk('public')->path($filePath),
            'import_type' => static::class,
            'user_id' => Auth::id(),
            'status' => Enum\Import\StatusImportacaoEnum::PENDENTE,
            'options' => json_encode($options),
            'started_at' => now(),
        ]);

        return $log;
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

        Log::debug("Arquivo validado: {$filePath}");

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

    protected function processRows(array $rows, ExcelImportInterface $importer, Models\ImportLog $importLog, array $options): void
    {
        $headers = array_shift($rows); // Remove cabeçalho
        $batchSize = $options['batch_size'] ?? 100;

        $batches = array_chunk($rows, $batchSize);
        $totalBatches = count($batches);

        //TODO: Melhorar isso aqui, separar
        $importLog->update([
            'total_rows'    => count($rows),
            'total_batches' => $totalBatches,
        ]);

        Log::debug("Total de linhas: " . count($rows));
        Log::debug("Total de lotes: " . $totalBatches);

        foreach ($batches as $batch) {
            ProcessImportRowJob::dispatch($batch, $headers, get_class($importer), $importLog->id);
            // FinalizeImportJob::dispatch($importLog->id);
        }
    }

    protected function finalizeImportLog(Models\ImportLog $importLog): void
    {
        if($this->processedRows == 0) {
            $status = Enum\Import\StatusImportacaoEnum::CONCLUIDO;
        } elseif ($this->errorRows > 0) {
            $status = Enum\Import\StatusImportacaoEnum::CONCLUIDO_COM_ERROS;
        } else {
            $status = Enum\Import\StatusImportacaoEnum::CONCLUIDO;
        }

        $importLog->update([
            'status'        => $status,
            'total_rows'    => $this->processedRows,
            'success_rows'  => $this->successRows,
            'error_rows'    => $this->errorRows,
            'errors'        => json_encode($this->errors),
            'warnings'      => json_encode($this->warnings),
            'finished_at'   => now(),
        ]);
    }

}

