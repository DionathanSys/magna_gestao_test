<?php

namespace App\Services\Import;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
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

    public function import(string $filePath, ExcelImportInterface $importer, array $options = []): array
    {
        try {
            Log::debug('Iniciando importação', [
                'file_path' => Storage::disk('public')->path($filePath),
                'options' => $options
            ]);

            DB::beginTransaction();

            // Criar log de importação
            $importLog = $this->createImportLog($filePath, $options);

            // Validar arquivo
            $this->validateFile($filePath);

            // Ler arquivo Excel
            $spreadsheet = IOFactory::load(Storage::disk('public')->path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validar cabeçalho
            $this->validateHeaders($rows[0] ?? [], $importer);

            // Processar linhas
            $this->processRows($rows, $importer, $importLog, $options);

            // Finalizar log
            $this->finalizeImportLog($importLog);

            DB::commit();

            $this->setSuccess("Importação concluída. {$this->successRows} registros processados com sucesso.");

            return [
                'import_log_id' => $importLog->id,
                'total_rows' => $this->processedRows,
                'success_rows' => $this->successRows,
                'error_rows' => $this->errorRows,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->setError("Erro na importação: " . $e->getMessage());

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

        Log::debug('ImportLog Criado', ['log' => $log]);

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

        Log::debug('Arquivo validado');

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

        Log::debug('Cabeçalho validado', ['headers' => $headers]);
    }

    protected function processRows(array $rows, ExcelImportInterface $importer, Models\ImportLog $importLog, array $options): void
    {
        $headers = array_shift($rows); // Remove cabeçalho
        $batchSize = $options['batch_size'] ?? 100;
        $useQueue = $options['use_queue'] ?? false;

        foreach (array_chunk($rows, $batchSize) as $batch) {

            if ($useQueue) {
                ProcessImportRowJob::dispatch($batch, $headers, $importer, $importLog->id);
            } else {
                $this->processBatch($batch, $headers, $importer, $importLog);
            }
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
            'status' => $this->errorRows > 0 ? 'CONCLUIDO_COM_ERROS' : 'CONCLUIDO',
            'total_rows' => $this->processedRows,
            'success_rows' => $this->successRows,
            'error_rows' => $this->errorRows,
            'errors' => json_encode($this->errors),
            'warnings' => json_encode($this->warnings),
            'finished_at' => now(),
        ]);
    }
}
