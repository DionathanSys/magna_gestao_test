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

            // Apenas aciona a beginTransaction se não estiver usando fila
            if (!($options['use_queue'] ?? false)) {
                DB::beginTransaction();
            }

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

            if (!($options['use_queue'] ?? false)) {
                DB::commit();
            }

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

            if (!($options['use_queue'] ?? false)) {
                DB::rollBack();
            }

            $this->setError("Erro na importação: " . $e->getMessage());

            $this->finalizeImportLog($this->importLog);

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
            'file_name' => basename($filePath),
            'file_path' => Storage::disk('public')->path($filePath),
            'import_type' => static::class,
            'user_id' => Auth::id(),
            'status' => Enum\Import\StatusImportacaoEnum::PROCESSANDO,
            'options' => json_encode($options),
            'started_at' => now(),
        ]);

        Log::debug("Log de importação criado: ID {$log->id}");

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
        $useQueue = $options['use_queue'] ?? false;

        $batches = array_chunk($rows, $batchSize);
        $totalBatches = count($batches);

        $importLog->update([
            'total_rows' => count($rows),
            'total_batches' => $totalBatches,
        ]);

        Log::debug("Processando {$totalBatches} lotes de tamanho {$batchSize}, uso de fila: " . ($useQueue ? 'sim' : 'não'), ['import_log_id' => $importLog->id, 'options' => $options]);

        foreach ($batches as $batch) {

            if ($useQueue) {
                ProcessImportRowJob::dispatch($batch, $headers, get_class($importer), $importLog->id);
            } else {
                $this->processBatch($batch, $headers, $importer, $importLog);
            }
        }

        if ($useQueue) {
            // Disparar job para finalizar a importação
            FinalizeImportJob::dispatch($importLog->id);
        }
    }

    protected function processBatch(array $batch, array $headers, ExcelImportInterface $importer, Models\ImportLog $importLog): void
    {
        foreach ($batch as $index => $row) {
            $rowNumber = $index + 2; // +2 porque começamos do 0 e pulamos o cabeçalho

            try {
                // Combinar cabeçalhos com dados da linha
                $rowData = array_combine($headers, $row);

                // Validar linha
                $validationErrors = $importer->validate($rowData, $rowNumber);
                if (!empty($validationErrors)) {
                    $this->errors[] = "Linha {$rowNumber}: " . implode(', ', $validationErrors);
                    $this->errorRows++;
                    continue;
                }

                // Transformar dados
                $transformedData = $importer->transform($rowData);

                // Processar linha
                $result = $importer->process($transformedData);

                if ($result) {
                    $this->successRows++;
                } else {
                    $this->errorRows++;
                    $this->errors[] = "Linha {$rowNumber}: Erro no processamento.";
                }

                $this->processedRows++;
            } catch (\Exception $e) {
                $this->errorRows++;
                $this->errors[] = "Linha {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    protected function finalizeImportLog(Models\ImportLog $importLog): void
    {
        $importLog->update([
            'status' => $this->errorRows > 0 ? Enum\Import\StatusImportacaoEnum::CONCLUIDO_COM_ERROS : Enum\Import\StatusImportacaoEnum::CONCLUIDO,
            'total_rows' => $this->processedRows,
            'success_rows' => $this->successRows,
            'error_rows' => $this->errorRows,
            'errors' => json_encode($this->errors),
            'warnings' => json_encode($this->warnings),
            'finished_at' => now(),
        ]);
    }
}
