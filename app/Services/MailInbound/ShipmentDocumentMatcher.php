<?php

namespace App\Services\MailInbound;

use App\Events\MailInbound\ShipmentDocumentsMatched;
use App\Models\ReceivedFiscalDocument;
use App\Models\ShipmentDocumentGroup;
use Illuminate\Support\Facades\DB;

class ShipmentDocumentMatcher
{
    public function match(ReceivedFiscalDocument $document): ?ShipmentDocumentGroup
    {
        return DB::transaction(function () use ($document) {
            if ($document->tipo_documento === 'sale') {
                $remittance = ReceivedFiscalDocument::query()
                    ->where('tipo_documento', 'remittance')
                    ->where('chave_nfe', $document->referenced_nfe_key)
                    ->first();

                if (! $remittance) {
                    return null;
                }

                return $this->upsertGroup($document, $remittance);
            }

            if ($document->tipo_documento === 'remittance') {
                $sale = ReceivedFiscalDocument::query()
                    ->where('tipo_documento', 'sale')
                    ->where('numero_nota', $document->referenced_sale_number)
                    ->first();

                if (! $sale) {
                    return null;
                }

                return $this->upsertGroup($sale, $document);
            }

            return null;
        });
    }

    protected function upsertGroup(ReceivedFiscalDocument $sale, ReceivedFiscalDocument $remittance): ShipmentDocumentGroup
    {
        $group = ShipmentDocumentGroup::query()->updateOrCreate(
            [
                'sale_document_id' => $sale->id,
                'remittance_document_id' => $remittance->id,
            ],
            [
                'sale_nfe_key' => $sale->chave_nfe,
                'remittance_nfe_key' => $remittance->chave_nfe,
                'sale_number' => $sale->numero_nota,
                'remittance_number' => $remittance->numero_nota,
                'integrado_id' => $remittance->integrado_id,
                'status' => 'matched',
                'matched_at' => now(),
                'payload' => [],
            ]
        );

        if ($group->status === 'matched' && ! $group->viagem_id) {
            event(new ShipmentDocumentsMatched($group->id));
        }

        return $group;
    }
}
