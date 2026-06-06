<?php

namespace App\Services\MailInbound;

use App\Models\Integrado;
use App\Models\ReceivedFiscalDocument;
use App\Models\ShipmentDocumentGroup;
use Illuminate\Support\Facades\DB;

class LinkFiscalDocumentToIntegradoService
{
    public function __construct(protected ShipmentTripService $shipmentTripService)
    {
    }

    public function handle(ReceivedFiscalDocument $document, Integrado $integrado): void
    {
        DB::transaction(function () use ($document, $integrado): void {
            $integrado->update([
                'documento' => $document->destinatario_documento ?: $integrado->documento,
                'nome' => $document->destinatario_nome ?: $integrado->nome,
            ]);

            $document->update([
                'integrado_id' => $integrado->id,
            ]);

            ShipmentDocumentGroup::query()
                ->where('remittance_document_id', $document->id)
                ->get()
                ->each(function (ShipmentDocumentGroup $group) use ($integrado): void {
                    $group->update([
                        'integrado_id' => $integrado->id,
                        'status' => $group->viagem_id ? $group->status : 'matched',
                    ]);

                    if (! $group->viagem_id) {
                        $this->shipmentTripService->createFromGroup($group->id);
                    }
                });
        });
    }
}
