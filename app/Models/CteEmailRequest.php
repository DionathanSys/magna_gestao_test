<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CteEmailRequest extends Model
{
    protected $casts = [
        'requested_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_response_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CteEmailRequestMessage::class);
    }
}
