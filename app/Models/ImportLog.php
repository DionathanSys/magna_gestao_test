<?php

namespace App\Models;

use App\Enum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ImportLog extends Model
{
     protected $casts = [
        'status'                => Enum\Import\StatusImportacaoEnum::class,
        'errors'                => 'array',
        'warnings'              => 'array',
        'skipped_reasons'       => 'array',
        'options'               => 'array',
        'mapping'               => 'array',
        'summary'               => 'array',
        'started_at'            => 'datetime',
        'finished_at'           => 'datetime',
        'progress_percentage'   => 'decimal:2',
    ];

    // Relacionamentos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    protected function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->duration_seconds) {
                    return null;
                }

                $minutes = floor($this->duration_seconds / 60);
                $seconds = $this->duration_seconds % 60;

                return sprintf('%02d:%02d', $minutes, $seconds);
            }
        );
    }

    protected function progressPercentageFormatted(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format($this->progress_percentage, 1) . '%'
        );
    }

    protected function fileSizeFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->file_size) {
                    return null;
                }

                $bytes = $this->file_size;
                $units = ['B', 'KB', 'MB', 'GB'];

                for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                    $bytes /= 1024;
                }

                return round($bytes, 2) . ' ' . $units[$i];
            }
        );
    }

    protected function statusBadgeColor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->status) {
                'PENDING' => 'gray',
                'PROCESSING' => 'info',
                'COMPLETED' => 'success',
                'COMPLETED_WITH_ERRORS' => 'warning',
                'FAILED' => 'danger',
                'CANCELLED' => 'secondary',
                default => 'gray'
            }
        );
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('import_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['COMPLETED', 'COMPLETED_WITH_ERRORS']);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'PROCESSING');
    }

    // Métodos úteis
    public function isCompleted(): bool
    {
        return in_array($this->status, [Enum\Import\StatusImportacaoEnum::CONCLUIDO, Enum\Import\StatusImportacaoEnum::CONCLUIDO_COM_ERROS]);
    }

    public function isInProgress(): bool
    {
        return $this->status === Enum\Import\StatusImportacaoEnum::PROCESSANDO;
    }

    public function hasFailed(): bool
    {
        return $this->status === Enum\Import\StatusImportacaoEnum::FALHOU;
    }

    public function hasErrors(): bool
    {
        return $this->error_rows > 0;
    }

    public function hasWarnings(): bool
    {
        return $this->warning_rows > 0;
    }

    public function calculateDuration(): void
    {
        if ($this->started_at && $this->finished_at) {
            $this->duration_seconds = $this->finished_at->diffInSeconds($this->started_at);
            $this->save();
        }
    }

    public function updateProgress(): void
    {
        if ($this->total_batches > 0) {
            $this->progress_percentage = ($this->processed_batches / $this->total_batches) * 100;
            $this->save();
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => $this->error_rows > 0 ? 'COMPLETED_WITH_ERRORS' : 'COMPLETED',
            'finished_at' => now(),
        ]);

        $this->calculateDuration();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $errors = $this->errors ?? [];
        $errors[] = $errorMessage;

        $this->update([
            'status' => 'FAILED',
            'finished_at' => now(),
            'errors' => $errors,
        ]);

        $this->calculateDuration();
    }
}
