<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function cteReturnMessages(): HasMany
    {
        return $this->hasMany(CteEmailRequestMessage::class, 'incoming_email_id');
    }

    protected function pendingSummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->fiscalDocument) {
                    return 'Documento fiscal gerado';
                }

                $attachments = $this->relationLoaded('attachments') ? $this->attachments : $this->attachments()->get();

                if ($attachments->isEmpty()) {
                    return 'Email sem anexos';
                }

                if (! $attachments->contains('kind', 'xml')) {
                    return 'Email sem XML para parse';
                }

                if ($this->status === 'stored') {
                    return 'Aguardando processamento fiscal';
                }

                if ($this->status === 'ignored') {
                    return $this->error_message ?: 'Email ignorado';
                }

                return 'Pendente de analise';
            }
        );
    }
}
