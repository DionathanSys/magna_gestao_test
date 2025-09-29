<?php

namespace App\Services\Import;

use App\{Models, Enum};
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        Log::debug("Criando log de importação para o arquivo: " . $filePath);

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

    public function incrementSuccessRows(): void
    {
        $this->importLog->increment('success_rows');

    }

    public function incrementErrorRows(array $errors): void
    {
        $this->importLog->increment('error_rows');

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
        Log::debug("Marcando log de importação como falhado: " . $this->importLog->id);

        $this->importLog->markAsFailed("Erro na importação");
    }
}
