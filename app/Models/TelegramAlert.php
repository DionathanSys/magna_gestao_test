<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TelegramAlert extends Model
{
    protected $fillable = [
        'created_by',
        'message_html',
        'telegram_options',
        'file_path',
        'file_disk',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_options' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(TelegramAlertRecipient::class);
    }
}
