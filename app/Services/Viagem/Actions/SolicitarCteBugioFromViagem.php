<?php

namespace App\Services\Viagem\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Jobs\SolicitarCteBugio;
use App\Models\DocumentoFrete;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\Viagem;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SolicitarCteBugioFromViagem
{
    public function handle(Viagem $viagem, array $data): void
    {
        $viagem->loadMissing([
            'attachments.incomingEmailAttachment',
            'attachments.receivedFiscalDocument',
        ]);

        $integrado = Integrado::query()->findOrFail($data['integrado_id']);
        $veiculo = Veiculo::query()->findOrFail($viagem->veiculo_id);
        $motoristaCpf = $data['motorista'];
        $motoristaNome = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $motoristaCpf)['motorista'] ?? null;
        $tipoDocumento = $data['tipo_documento'];
        $kmRota = (float) ($data['km_rota'] ?? 0);
        $dataCompetencia = (string) $data['data_competencia'];

        $anexos = $viagem->attachments
            ->map(fn ($attachment) => $attachment->incomingEmailAttachment)
            ->filter()
            ->pluck('path')
            ->filter(fn (?string $path) => filled($path) && Storage::disk('local')->exists($path))
            ->unique()
            ->values()
            ->all();

        if ($anexos === []) {
            throw new \InvalidArgumentException('A viagem não possui anexos válidos.');
        }

        $fiscalDocuments = $viagem->attachments
            ->map(fn ($attachment) => $attachment->receivedFiscalDocument)
            ->filter()
            ->unique('id')
            ->values();

        $saleDocument = $fiscalDocuments->firstWhere('tipo_documento', 'sale') ?? $fiscalDocuments->first();
        $remittanceDocument = $fiscalDocuments->firstWhere('tipo_documento', 'remittance');

        $nroNotas = $fiscalDocuments
            ->pluck('numero_nota')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($nroNotas === []) {
            throw new \InvalidArgumentException('A viagem não possui notas fiscais vinculadas nos anexos.');
        }

        $pesoCarga = isset($data['peso_carga'])
            ? (float) $data['peso_carga']
            : (float) ($remittanceDocument?->peso_carga ?? $saleDocument?->peso_carga ?? 0);

        if ($tipoDocumento === TipoDocumentoEnum::NFS->value) {
            $documentoFrete = $this->createDocumentoFrete($viagem, $veiculo, $integrado, $saleDocument, $dataCompetencia, $kmRota, $nroNotas);

            if (! $documentoFrete instanceof DocumentoFrete) {
                throw new \RuntimeException('Não foi possível criar o Documento de Frete.');
            }

            return;
        }

        $payload = [
            'km_total' => $kmRota,
            'valor_frete' => $kmRota * db_config('config-bugio.valor-quilometro', 0),
            'anexos' => $anexos,
            'viagem_id' => $viagem->id,
            'integrado_id' => $integrado->id,
            'documento_transporte' => $viagem->documento_transporte,
            'destinos' => [[
                'integrado_id' => $integrado->id,
                'km_rota' => $kmRota,
                'integrado_nome' => $integrado->nome,
            ]],
            'veiculo' => $veiculo->placa,
            'created_by' => Auth::id() ?? $viagem->created_by,
            'nro_notas' => $nroNotas,
            'cte_retroativo' => (bool) ($data['cte_retroativo'] ?? true),
            'cte_complementar' => $tipoDocumento === TipoDocumentoEnum::CTE_COMPLEMENTO->value,
            'cte_referencia' => $data['cte_referencia'] ?? null,
            'motorista' => [
                'cpf' => $motoristaCpf,
                'nome' => $motoristaNome,
            ],
            'peso_carga' => $pesoCarga,
            'data_competencia' => $dataCompetencia,
        ];

        Log::info('Disparando solicitação de CTe a partir da viagem', [
            'viagem_id' => $viagem->id,
            'tipo_documento' => $tipoDocumento,
            'veiculo_id' => $veiculo->id,
            'integrado_id' => $integrado->id,
            'km_rota' => $kmRota,
            'peso_carga' => $pesoCarga,
            'nro_notas' => $nroNotas,
        ]);

        SolicitarCteBugio::dispatch($payload)->onConnection('database');
    }

    public function handleAgrupado(Collection $viagens, array $data): void
    {
        $viagens = $viagens
            ->filter(fn (Viagem $viagem): bool => $viagem->exists)
            ->values();

        if ($viagens->count() < 2) {
            throw new \InvalidArgumentException('Selecione pelo menos duas viagens para agrupar a solicitação.');
        }

        $viagens->load([
            'attachments.incomingEmailAttachment',
            'attachments.receivedFiscalDocument',
            'cargas.integrado',
            'veiculo',
        ]);

        if ($viagens->pluck('veiculo_id')->filter()->unique()->count() !== 1) {
            throw new \InvalidArgumentException('Todas as viagens selecionadas devem ser do mesmo veículo.');
        }

        $documentosTransporte = $viagens
            ->map(fn (Viagem $viagem): ?string => $this->documentoTransporteReal($viagem))
            ->filter()
            ->unique()
            ->values();

        if ($documentosTransporte->count() > 1) {
            throw new \InvalidArgumentException('As viagens selecionadas possuem documentos de transporte diferentes.');
        }

        $documentoTransporte = $documentosTransporte->first()
            ?? 'AGR-'.now()->format('YmdHis').'-'.$viagens->pluck('id')->take(3)->implode('-');

        $viagens->each(function (Viagem $viagem) use ($documentoTransporte): void {
            if ($viagem->documento_transporte !== $documentoTransporte) {
                $viagem->update(['documento_transporte' => $documentoTransporte]);
            }
        });

        $integrado = Integrado::query()->findOrFail($data['integrado_id']);
        $viagemReferencia = $viagens->first();
        $veiculo = Veiculo::query()->findOrFail($viagemReferencia->veiculo_id);

        $integradosSelecionados = $viagens
            ->flatMap(fn (Viagem $viagem) => $viagem->cargas)
            ->map(fn ($carga) => $carga->integrado?->id)
            ->filter()
            ->unique();

        if (! $integradosSelecionados->contains($integrado->id)) {
            throw new \InvalidArgumentException('O integrado informado deve estar vinculado a uma das viagens selecionadas.');
        }

        $motoristaCpf = $data['motorista'];
        $motoristaNome = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $motoristaCpf)['motorista'] ?? null;
        $tipoDocumento = $data['tipo_documento'];
        $kmRota = (float) ($data['km_rota'] ?? 0);
        $dataCompetencia = (string) $data['data_competencia'];

        $anexos = $viagens
            ->flatMap(fn (Viagem $viagem) => $viagem->attachments)
            ->map(fn ($attachment) => $attachment->incomingEmailAttachment)
            ->filter()
            ->pluck('path')
            ->filter(fn (?string $path) => filled($path) && Storage::disk('local')->exists($path))
            ->unique()
            ->values()
            ->all();

        if ($anexos === []) {
            throw new \InvalidArgumentException('As viagens selecionadas não possuem anexos válidos.');
        }

        $fiscalDocuments = $viagens
            ->flatMap(fn (Viagem $viagem) => $viagem->attachments)
            ->map(fn ($attachment) => $attachment->receivedFiscalDocument)
            ->filter()
            ->unique('id')
            ->values();

        $saleDocument = $fiscalDocuments->firstWhere('tipo_documento', 'sale') ?? $fiscalDocuments->first();

        $nroNotas = $fiscalDocuments
            ->pluck('numero_nota')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($nroNotas === []) {
            throw new \InvalidArgumentException('As viagens selecionadas não possuem notas fiscais vinculadas nos anexos.');
        }

        $pesoCarga = isset($data['peso_carga'])
            ? (float) $data['peso_carga']
            : (float) $fiscalDocuments->sum(fn ($document) => (float) ($document?->peso_carga ?? 0));

        if ($tipoDocumento === TipoDocumentoEnum::NFS->value) {
            $documentoFrete = $this->createDocumentoFrete($viagemReferencia, $veiculo, $integrado, $saleDocument, $dataCompetencia, $kmRota, $nroNotas);

            if (! $documentoFrete instanceof DocumentoFrete) {
                throw new \RuntimeException('Não foi possível criar o Documento de Frete.');
            }

            $documentoFrete->update(['documento_transporte' => $documentoTransporte]);

            return;
        }

        $payload = [
            'km_total' => $kmRota,
            'valor_frete' => $kmRota * db_config('config-bugio.valor-quilometro', 0),
            'anexos' => $anexos,
            'viagem_id' => $viagemReferencia->id,
            'integrado_id' => $integrado->id,
            'documento_transporte' => $documentoTransporte,
            'destinos' => [[
                'integrado_id' => $integrado->id,
                'km_rota' => $kmRota,
                'integrado_nome' => $integrado->nome,
            ]],
            'veiculo' => $veiculo->placa,
            'created_by' => Auth::id() ?? $viagemReferencia->created_by,
            'nro_notas' => $nroNotas,
            'cte_retroativo' => (bool) ($data['cte_retroativo'] ?? true),
            'cte_complementar' => $tipoDocumento === TipoDocumentoEnum::CTE_COMPLEMENTO->value,
            'cte_referencia' => $data['cte_referencia'] ?? null,
            'motorista' => [
                'cpf' => $motoristaCpf,
                'nome' => $motoristaNome,
            ],
            'peso_carga' => $pesoCarga,
            'data_competencia' => $dataCompetencia,
        ];

        Log::info('Disparando solicitação agrupada de CTe a partir de viagens', [
            'viagens_ids' => $viagens->pluck('id')->all(),
            'documento_transporte' => $documentoTransporte,
            'tipo_documento' => $tipoDocumento,
            'veiculo_id' => $veiculo->id,
            'integrado_id' => $integrado->id,
            'km_rota' => $kmRota,
            'peso_carga' => $pesoCarga,
            'nro_notas' => $nroNotas,
        ]);

        SolicitarCteBugio::dispatch($payload)->onConnection('database');
    }

    protected function createDocumentoFrete(
        Viagem $viagem,
        Veiculo $veiculo,
        Integrado $integrado,
        mixed $saleDocument,
        string $dataCompetencia,
        float $kmRota,
        array $nroNotas,
    ): ?DocumentoFrete {
        if (! $saleDocument) {
            throw new \InvalidArgumentException('Não foi encontrado documento fiscal base para a NFS.');
        }

        $valorFrete = $kmRota * db_config('config-bugio.valor-quilometro', 0);

        return (new DocumentoFreteService)->criarDocumentoFrete([
            'veiculo_id' => $veiculo->id,
            'parceiro_destino' => $integrado->nome,
            'parceiro_origem' => $saleDocument->emitente_nome ?? 'BUGIO NUTRICAO',
            'numero_documento' => $saleDocument->numero_nota ?? ($nroNotas[0] ?? $viagem->documento_transporte),
            'documento_transporte' => $viagem->documento_transporte,
            'data_emissao' => $saleDocument->emitido_em?->format('Y-m-d H:i:s') ?? $dataCompetencia,
            'valor_total' => $valorFrete,
            'valor_icms' => 0,
            'tipo_documento' => TipoDocumentoEnum::NFS,
            'viagem_id' => $viagem->id,
        ]);
    }

    protected function documentoTransporteReal(Viagem $viagem): ?string
    {
        $documentoTransporte = trim((string) $viagem->documento_transporte);

        if ($documentoTransporte === '') {
            return null;
        }

        return $documentoTransporte !== trim((string) $viagem->numero_viagem)
            ? $documentoTransporte
            : null;
    }
}
