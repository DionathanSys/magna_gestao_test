<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViagemAttachment extends Model
{
    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function incomingEmailAttachment(): BelongsTo
    {
        return $this->belongsTo(IncomingEmailAttachment::class);
    }

    public function receivedFiscalDocument(): BelongsTo
    {
        return $this->belongsTo(ReceivedFiscalDocument::class);
    }
}
