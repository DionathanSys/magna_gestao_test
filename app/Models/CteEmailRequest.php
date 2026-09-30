<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CteEmailRequest extends Model
{
    public const ORIGIN_MANUAL = 'manual';

    public const ORIGIN_MAIL_INBOUND = 'mail_inbound';

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_response_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payload' => 'array',
        'nfe_keys' => 'array',
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function shipmentDocumentGroup(): BelongsTo
    {
        return $this->belongsTo(ShipmentDocumentGroup::class);
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CteEmailRequestMessage::class);
    }

    public function documentosFrete(): HasMany
    {
        return $this->hasMany(DocumentoFrete::class, 'cte_email_request_id');
    }
}
