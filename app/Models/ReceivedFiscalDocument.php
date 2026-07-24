<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function pendingSummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->tipo_documento === 'unknown') {
                    return 'Documento fora da regra fiscal configurada';
                }

                if ($this->tipo_documento === 'sale' && ! $this->referenced_nfe_key) {
                    return 'Nota de venda sem chave referenciada da remessa';
                }

                if ($this->tipo_documento === 'remittance' && ! $this->referenced_sale_number) {
                    return 'Nota de remessa sem numero da venda em infAdic';
                }

                if ($this->tipo_documento === 'remittance' && ! $this->integrado_id) {
                    return 'Documento do destinatario nao encontrado em Integrados';
                }

                $hasGroup = $this->relationLoaded('saleGroups')
                    ? $this->saleGroups->isNotEmpty()
                    : $this->saleGroups()->exists();

                $hasGroup = $hasGroup || ($this->relationLoaded('remittanceGroups')
                    ? $this->remittanceGroups->isNotEmpty()
                    : $this->remittanceGroups()->exists());

                if (! $hasGroup) {
                    return 'Aguardando documento par para montar a carga';
                }

                return 'Pareado ou concluido';
            }
        );
    }
}
