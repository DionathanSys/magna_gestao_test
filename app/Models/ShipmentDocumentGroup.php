<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentDocumentGroup extends Model
{
    protected $casts = [
        'matched_at' => 'datetime',
        'payload' => 'array',
    ];

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(ReceivedFiscalDocument::class, 'sale_document_id');
    }

    public function remittanceDocument(): BelongsTo
    {
        return $this->belongsTo(ReceivedFiscalDocument::class, 'remittance_document_id');
    }

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }
}
