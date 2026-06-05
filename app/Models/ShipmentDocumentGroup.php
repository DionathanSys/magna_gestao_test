<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    protected function pendingSummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->status === 'trip_created') {
                    return 'Viagem criada';
                }

                if ($this->status === 'failed') {
                    return 'Falha ao criar viagem';
                }

                if ($this->status === 'pending_data') {
                    $payload = collect($this->payload ?? []);
                    $missing = [];

                    if (! $payload->get('integrado_id')) {
                        $missing[] = 'integrado';
                    }

                    if (! $payload->get('unidade_negocio')) {
                        $missing[] = 'unidade de negocio';
                    }

                    if (! $payload->get('veiculo_id')) {
                        $missing[] = 'veiculo pela placa';
                    }

                    return $missing === []
                        ? 'Pendente de dados complementares'
                        : 'Falta: ' . implode(', ', $missing);
                }

                return 'Pareado e aguardando criacao da viagem';
            }
        );
    }
}
