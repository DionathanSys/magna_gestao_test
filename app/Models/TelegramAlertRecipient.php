<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAlertRecipient extends Model
{
    protected $fillable = [
        'telegram_alert_id',
        'user_id',
        'chat_id',
        'status',
        'telegram_message_id',
        'telegram_document_message_id',
        'error',
        'message_sent_at',
        'document_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'message_sent_at' => 'datetime',
            'document_sent_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(TelegramAlert::class, 'telegram_alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
