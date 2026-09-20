<?php

namespace App\Models;

use App\Enum\Automation\AutomationResultImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationResultImport extends Model
{
    protected $table = 'automation_result_imports';

    protected $casts = [
        'status' => AutomationResultImportStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'next_cursor' => 'string',
    ];

    public function automationJob(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class);
    }
}
