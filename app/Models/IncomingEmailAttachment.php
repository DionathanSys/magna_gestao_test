<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingEmailAttachment extends Model
{
    protected $casts = [
        'metadata' => 'array',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(IncomingEmail::class, 'incoming_email_id');
    }

    public function viagemAttachments(): HasMany
    {
        return $this->hasMany(ViagemAttachment::class);
    }
}
