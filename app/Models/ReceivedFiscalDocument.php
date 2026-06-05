<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivedFiscalDocument extends Model
{
    protected $casts = [
        'emitido_em' => 'datetime',
        'payload' => 'array',
        'matched_at' => 'datetime',
    ];

    public function incomingEmail(): BelongsTo
    {
        return $this->belongsTo(IncomingEmail::class);
    }

    public function xmlAttachment(): BelongsTo
    {
        return $this->belongsTo(IncomingEmailAttachment::class, 'xml_attachment_id');
    }

    public function pdfAttachment(): BelongsTo
    {
        return $this->belongsTo(IncomingEmailAttachment::class, 'pdf_attachment_id');
    }

    public function saleGroups(): HasMany
    {
        return $this->hasMany(ShipmentDocumentGroup::class, 'sale_document_id');
    }

    public function remittanceGroups(): HasMany
    {
        return $this->hasMany(ShipmentDocumentGroup::class, 'remittance_document_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class);
    }
}
