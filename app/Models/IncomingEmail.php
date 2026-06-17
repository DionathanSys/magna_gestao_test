<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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

    protected function capturedDocumentType(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->fiscalDocument?->tipo_documento
                ?? $this->firstProcessedCteAttachmentMetadata()['tipo_documento']
                ?? $this->firstCteReturnRequest()?->tipo_documento_solicitado
        );
    }

    protected function capturedDocumentNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->fiscalDocument?->numero_nota
                ?? $this->firstProcessedCteAttachmentMetadata()['numero_documento']
        );
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

    protected function firstProcessedCteAttachmentMetadata(): array
    {
        $attachments = $this->relationLoaded('attachments')
            ? $this->attachments
            : $this->attachments()->get();

        return $attachments
            ->pluck('metadata')
            ->filter(fn ($metadata): bool => is_array($metadata) && (($metadata['cte_return'] ?? null) === 'document_created'))
            ->first() ?? [];
    }

    protected function firstCteReturnRequest(): ?CteEmailRequest
    {
        $messages = $this->relationLoaded('cteReturnMessages')
            ? $this->cteReturnMessages
            : $this->cteReturnMessages()->with('request')->get();

        $message = $messages instanceof Collection
            ? $messages->first()
            : null;

        if (! $message) {
            return null;
        }

        return $message->relationLoaded('request')
            ? $message->request
            : $message->request()->first();
    }
}
