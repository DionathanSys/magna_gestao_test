<?php

namespace App\Models;

use App\Enum\Automation\AutomationJobSource;
use App\Enum\Automation\AutomationJobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationJob extends Model
{
    protected $table = 'automation_jobs';

    protected $casts = [
        'status' => AutomationJobStatus::class,
        'source' => AutomationJobSource::class,
        'parameters' => 'array',
        'metadata' => 'array',
        'submission_retryable' => 'boolean',
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_submission_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retry_of_job_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AutomationEvent::class, 'provider_job_id', 'provider_job_id');
    }

    public function resultImports(): HasMany
    {
        return $this->hasMany(AutomationResultImport::class);
    }

    public function isAwaitingSubmission(): bool
    {
        return $this->status?->isAwaitingSubmission() ?? false;
    }
}
