<?php

namespace App\Services\MailInbound;

use App\Enum\ClienteEnum;
use App\Models\Integrado;
use App\Models\ShipmentDocumentGroup;
use App\Models\Veiculo;
use App\Models\Viagem;
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

        $payload = collect($group->payload ?? []);

        if ($group->viagem_id && $group->viagem()->exists()) {
            return;
        }

        $integradoId = $group->integrado_id;
        $unidadeNegocio = $this->config->unidadeNegocio();
        $placa = $group->remittanceDocument?->placa_transportador ?: $group->saleDocument?->placa_transportador;
        $veiculoId = $placa ? $this->veiculoService->getVeiculoIdByPlaca($placa) : null;
        $veiculoId ??= $payload->get('veiculo_id');
        $placaManual = $payload->get('placa_manual');

        if (! $integradoId || ! $unidadeNegocio || ! $veiculoId) {
            $group->update([
                'status' => 'pending_data',
                'payload' => [
                    'integrado_id' => $integradoId,
                    'unidade_negocio' => $unidadeNegocio,
                    'placa_transportador' => $placa,
                    'placa_manual' => $placaManual,
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

    public function createManualBugioTrip(array $data): Viagem
    {
        return DB::transaction(function () use ($data): Viagem {
            $integrado = Integrado::query()->findOrFail($data['integrado_id']);
            $veiculo = Veiculo::query()->findOrFail($data['veiculo_id']);
            $unidadeNegocio = $this->config->unidadeNegocio() ?: $veiculo->filial;

            if (! $unidadeNegocio) {
                throw new \InvalidArgumentException('Nao foi possivel resolver a unidade de negocio para a Viagem Bugio.');
            }

            $numeroViagem = (new ViagemNumberService)
                ->next(ClienteEnum::BUGIO->prefixoViagem())['numero_viagem'];
            $documentoTransporte = (string) ($data['documento_transporte'] ?? $numeroViagem);

            $dataReferencia = $data['data_competencia'] ?? now()->toDateString();
            $kmRodado = (float) ($data['km_rodado'] ?? 0);
            $kmPago = (float) ($data['km_pago'] ?? 0);

            $viagem = $this->viagemService->create([
                'veiculo_id' => $veiculo->id,
                'unidade_negocio' => $unidadeNegocio,
                'cliente' => ClienteEnum::BUGIO->value,
                'numero_viagem' => $numeroViagem,
                'documento_transporte' => $documentoTransporte,
                'km_rodado' => $kmRodado,
                'km_pago' => $kmPago,
                'data_competencia' => $dataReferencia,
                'data_inicio' => $dataReferencia,
                'data_fim' => $dataReferencia,
                'total_destinos' => 1,
                'conferido' => false,
                'ignorar' => false,
                'possui_pendencia' => false,
            ]);

            if (! $viagem) {
                throw new \RuntimeException($this->viagemService->getMessage() ?: 'Nao foi possivel criar a Viagem Bugio.');
            }

            $this->cargaService->create($integrado, $viagem);

            return $viagem;
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
