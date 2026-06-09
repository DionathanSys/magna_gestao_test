<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CteEmailRequestMessage extends Model
{
    protected $casts = [
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CteEmailRequest::class, 'cte_email_request_id');
    }

    public function incomingEmail(): BelongsTo
    {
        return $this->belongsTo(IncomingEmail::class);
    }
}
