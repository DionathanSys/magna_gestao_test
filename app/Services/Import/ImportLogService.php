<?php

namespace App\Services\Import;

use App\{Models, Enum};
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportLogService
{
    use UserCheckTrait;

    protected Models\ImportLog $importLog;

    public function __construct(int $importLogId)
    {
        $this->importLog = Models\ImportLog::find($importLogId);
    }

    public static function createImportLog(string $filePath, array $options): Models\ImportLog
    {

        return Models\ImportLog::create([
            'import_description' => $options['descricao'] ?? 'Importação de dados',
            'file_name'     => basename($filePath),
            'file_path'     => Storage::disk('public')->path($filePath),
            'import_type'   => static::class,
            'user_id'       => Auth::id() ?? null,
            'status'        => Enum\Import\StatusImportacaoEnum::PENDENTE,
            'options'       => json_encode($options),
            'started_at'    => now(),
        ]);

    }

    public function update(array $data): void
    {
        $this->importLog->update($data);
    }

    public function incrementProcessedRows(): void
    {
        $this->importLog->increment('processed_rows');
    }

    public function incrementSuccessRows(): void
    {
        $this->importLog->increment('success_rows');
    }

    public function incrementErrorRows(array $errors): void
    {
        $this->importLog->increment('error_rows');

        //TODO: alterar propriedade para ser uma coluna virtual calculada
        $this->importLog->increment('processed_rows');

        //esse trecho precisa manter o valor que já existe no banco
        $existingErrors = $this->importLog->errors ? json_decode($this->importLog->errors, true) : [];
        $allErrors = array_merge($existingErrors, $errors);

        $this->importLog->update([
            'errors' => json_encode($allErrors)
        ]);
    }

    public function incrementBatchProcessed(): void
    {
        $this->importLog->increment('processed_batches');
    }

    public function failed(): void
    {
        $this->importLog->markAsFailed("Erro na importação");
    }
}
