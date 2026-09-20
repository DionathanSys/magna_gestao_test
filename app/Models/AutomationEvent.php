<?php

namespace App\Models;

use App\Enum\Automation\AutomationEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationEvent extends Model
{
    protected $table = 'automation_events';

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'processing_status' => AutomationEventStatus::class,
    ];

    public function automationJob(): BelongsTo
    {
        return $this->belongsTo(
            AutomationJob::class,
            'provider_job_id',
            'provider_job_id',
        );
    }
}
