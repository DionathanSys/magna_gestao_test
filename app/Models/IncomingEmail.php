<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncomingEmail extends Model
{
    protected $casts = [
        'received_at' => 'datetime',
        'raw_headers' => 'array',
        'metadata' => 'array',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(IncomingEmailAttachment::class);
    }

    public function fiscalDocument(): HasOne
    {
        return $this->hasOne(ReceivedFiscalDocument::class);
    }
}
