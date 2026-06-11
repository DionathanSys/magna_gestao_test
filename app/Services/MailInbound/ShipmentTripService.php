<?php

namespace App\Services\MailInbound;

use App\Enum\ClienteEnum;
use App\Models\ShipmentDocumentGroup;
use App\Models\ViagemAttachment;
use App\Services\Carga\CargaService;
use App\Services\Veiculo\VeiculoService;
use App\Services\Viagem\ViagemService;
use App\Services\ViagemNumberService;
use Illuminate\Support\Facades\DB;

class ShipmentTripService
{
    public function __construct(
        protected ViagemService $viagemService,
        protected CargaService $cargaService,
        protected VeiculoService $veiculoService,
        protected MailInboundConfig $config,
    ) {}

    public function createFromGroup(int $groupId): void
    {
        $group = ShipmentDocumentGroup::query()
            ->with([
                'saleDocument.xmlAttachment',
                'saleDocument.pdfAttachment',
                'remittanceDocument.xmlAttachment',
                'remittanceDocument.pdfAttachment',
            ])
            ->findOrFail($groupId);

        if ($group->viagem_id && $group->viagem()->exists()) {
            return;
        }

        $integradoId = $group->integrado_id;
        $unidadeNegocio = $this->config->unidadeNegocio();
        $placa = $group->remittanceDocument?->placa_transportador ?: $group->saleDocument?->placa_transportador;
        $veiculoId = $placa ? $this->veiculoService->getVeiculoIdByPlaca($placa) : null;

        if (! $integradoId || ! $unidadeNegocio || ! $veiculoId) {
            $group->update([
                'status' => 'pending_data',
                'payload' => [
                    'integrado_id' => $integradoId,
                    'unidade_negocio' => $unidadeNegocio,
                    'placa_transportador' => $placa,
                    'veiculo_id' => $veiculoId,
                ],
            ]);

            return;
        }

        DB::transaction(function () use ($group, $veiculoId, $unidadeNegocio) {
            $numeroViagem = (new ViagemNumberService)
                ->next(ClienteEnum::BUGIO->prefixoViagem())['numero_viagem'];

            $dataReferencia = $group->remittanceDocument?->emitido_em ?: $group->saleDocument?->emitido_em ?: now();

            $viagem = $this->viagemService->create([
                'veiculo_id' => $veiculoId,
                'unidade_negocio' => $unidadeNegocio,
                'cliente' => ClienteEnum::BUGIO->value,
                'numero_viagem' => $numeroViagem,
                'documento_transporte' => $numeroViagem,
                'data_competencia' => $dataReferencia->format('Y-m-d H:i:s'),
                'data_inicio' => $dataReferencia->format('Y-m-d H:i:s'),
                'data_fim' => $dataReferencia->format('Y-m-d H:i:s'),
                'total_destinos' => 1,
                'conferido' => false,
                'ignorar' => false,
                'possui_pendencia' => false,
            ]);

            if (! $viagem) {
                $group->update(['status' => 'failed']);

                return;
            }

            $integrado = $group->remittanceDocument?->integrado;
            $this->cargaService->create($integrado, $viagem);

            $group->update([
                'viagem_id' => $viagem->id,
                'status' => 'trip_created',
            ]);

            $this->attachDocumentFiles($viagem->id, $group);
        });
    }

    protected function attachDocumentFiles(int $viagemId, ShipmentDocumentGroup $group): void
    {
        $attachments = [
            ['role' => 'sale_xml', 'document' => $group->saleDocument, 'attachment' => $group->saleDocument?->xmlAttachment],
            ['role' => 'sale_pdf', 'document' => $group->saleDocument, 'attachment' => $group->saleDocument?->pdfAttachment],
            ['role' => 'remittance_xml', 'document' => $group->remittanceDocument, 'attachment' => $group->remittanceDocument?->xmlAttachment],
            ['role' => 'remittance_pdf', 'document' => $group->remittanceDocument, 'attachment' => $group->remittanceDocument?->pdfAttachment],
        ];

        foreach ($attachments as $row) {
            if (! $row['attachment']) {
                continue;
            }

            ViagemAttachment::query()->firstOrCreate([
                'viagem_id' => $viagemId,
                'incoming_email_attachment_id' => $row['attachment']->id,
            ], [
                'received_fiscal_document_id' => $row['document']?->id,
                'role' => $row['role'],
            ]);
        }
    }
}
