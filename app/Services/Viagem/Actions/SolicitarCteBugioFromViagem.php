<?php

namespace App\Services\Viagem\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\DocumentoFrete;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\Viagem;
use App\Services\Bugio\CteEmailQueueService;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SolicitarCteBugioFromViagem
{
    public function handle(Viagem $viagem, array $data): void
    {
        $prepared = $this->preparePayload($viagem, $data);
        $tipoDocumento = $prepared['tipo_documento'];
        $payload = $prepared['payload'];

        if ($tipoDocumento === TipoDocumentoEnum::NFS->value) {
            $documentoFrete = $this->createDocumentoFrete(
                $viagem,
                $prepared['veiculo'],
                $prepared['integrado'],
                $prepared['sale_document'],
                $payload['data_competencia'],
                $payload['destinos'][0]['km_rota'],
                $prepared['nro_notas'],
            );

            if (! $documentoFrete instanceof DocumentoFrete) {
                throw new \RuntimeException('Não foi possível criar o Documento de Frete.');
            }

            return;
        }

        Log::info('Disparando solicitação de CTe a partir da viagem', [
            'viagem_id' => $viagem->id,
            'tipo_documento' => $tipoDocumento,
            'veiculo_id' => $prepared['veiculo']->id,
            'integrado_id' => $prepared['integrado']->id,
            'km_rota' => $payload['km_total'],
            'peso_carga' => $payload['peso_carga'],
            'nro_notas' => $prepared['nro_notas'],
        ]);

        app(CteEmailQueueService::class)->enqueue($payload);
    }

    /**
     * Prepares the exact payload used by both the Filament action and the
     * automatic request created from an inbound fiscal email.
     *
     * @return array{payload: array<string, mixed>, tipo_documento: string, veiculo: Veiculo, integrado: Integrado, sale_document: mixed, nro_notas: array<int, mixed>}
     */
    public function preparePayload(Viagem $viagem, array $data): array
    {
        $viagem->loadMissing([
            'veiculo',
            'cargas.integrado',
            'attachments.incomingEmailAttachment',
            'attachments.receivedFiscalDocument',
        ]);

        $integrado = Integrado::query()->findOrFail($data['integrado_id'] ?? null);
        $veiculo = $viagem->veiculo ?: Veiculo::query()->findOrFail($viagem->veiculo_id);
        $tipoDocumento = (string) ($data['tipo_documento'] ?? '');

        if (! in_array($tipoDocumento, array_map(fn (TipoDocumentoEnum $type): string => $type->value, TipoDocumentoEnum::cases()), true)) {
            throw new \InvalidArgumentException('Tipo de documento inválido para a solicitação.');
        }

        if (! $viagem->cargas->contains(fn ($carga): bool => (int) $carga->integrado_id === (int) $integrado->id)) {
            throw new \InvalidArgumentException('O integrado informado não está vinculado à viagem.');
        }

        $motoristaCpf = trim((string) ($data['motorista'] ?? ''));
        $motorista = collect(db_config('config-bugio.motoristas'))
            ->first(fn (mixed $item): bool => is_array($item) && (string) ($item['cpf'] ?? '') === $motoristaCpf);

        if ($motoristaCpf === '' || ! is_array($motorista)) {
            throw new \InvalidArgumentException('O motorista informado não está cadastrado nas configurações do Bugio.');
        }

        if (in_array($tipoDocumento, [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value], true)
            && $this->isGuatambuMunicipio($integrado->municipio)) {
            throw new \DomainException('Não é possível solicitar CTe para integrado com município Guatambu.');
        }

        if ($tipoDocumento === TipoDocumentoEnum::CTE_COMPLEMENTO->value && blank($data['cte_referencia'] ?? null)) {
            throw new \InvalidArgumentException('O CTe de referência é obrigatório para complemento.');
        }

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

        $this->ensureDocumentsCanGenerateFreight($fiscalDocuments);

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

        $kmRota = (float) ($data['km_rota'] ?? $integrado->km_rota ?? 0);
        $valorFrete = $kmRota * (float) db_config('config-bugio.valor-quilometro', 0);

        if ($kmRota <= 0 || $valorFrete <= 0) {
            throw new \InvalidArgumentException('Não é possível solicitar CTe com valor de frete zero.');
        }

        $dataCompetencia = trim((string) ($data['data_competencia'] ?? ''));
        if ($dataCompetencia === '') {
            throw new \InvalidArgumentException('A data de competência é obrigatória.');
        }

        $pesoCarga = isset($data['peso_carga'])
            ? (float) $data['peso_carga']
            : (float) ($remittanceDocument?->peso_carga ?? $saleDocument?->peso_carga ?? 0);

        return [
            'payload' => [
                'km_total' => $kmRota,
                'valor_frete' => $valorFrete,
                'anexos' => $anexos,
                'viagem_id' => $viagem->id,
                'integrado_id' => $integrado->id,
                'integrado_cpf' => $integrado->documento,
                'documento_transporte' => $viagem->documento_transporte,
                'destinos' => [[
                    'integrado_id' => $integrado->id,
                    'km_rota' => $kmRota,
                    'integrado_nome' => $integrado->nome,
                    'integrado_municipio' => $integrado->municipio,
                ]],
                'veiculo' => $veiculo->placa,
                'created_by' => Auth::id() ?? $viagem->created_by,
                'nro_notas' => $nroNotas,
                'nfe_keys' => $this->nfeKeys($fiscalDocuments),
                'cte_retroativo' => (bool) ($data['cte_retroativo'] ?? true),
                'cte_complementar' => $tipoDocumento === TipoDocumentoEnum::CTE_COMPLEMENTO->value,
                'cte_referencia' => $data['cte_referencia'] ?? null,
                'motorista' => [
                    'cpf' => $motoristaCpf,
                    'nome' => $motorista['motorista'] ?? null,
                ],
                'peso_carga' => $pesoCarga,
                'data_competencia' => $dataCompetencia,
            ],
            'tipo_documento' => $tipoDocumento,
            'veiculo' => $veiculo,
            'integrado' => $integrado,
            'sale_document' => $saleDocument,
            'nro_notas' => $nroNotas,
        ];
    }

    public function isGuatambuMunicipio(?string $municipio): bool
    {
        $normalized = Str::of((string) $municipio)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();

        return str_contains($normalized, 'guatambu');
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

        $viagemReferencia = $viagens->first();
        $documentoTransporte = $documentosTransporte->first()
            ?? 'AGR-'.now()->format('YmdHi').'-'.trim((string) $viagemReferencia->numero_viagem);

        $viagens->each(function (Viagem $viagem) use ($documentoTransporte): void {
            if ($viagem->documento_transporte !== $documentoTransporte) {
                $viagem->update(['documento_transporte' => $documentoTransporte]);
            }
        });

        $integrado = Integrado::query()->findOrFail($data['integrado_id']);
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

        $this->ensureDocumentsCanGenerateFreight($fiscalDocuments);

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
        $valorFrete = $kmRota * (float) db_config('config-bugio.valor-quilometro', 0);

        if ($kmRota <= 0 || $valorFrete <= 0) {
            throw new \InvalidArgumentException('Não é possível solicitar CTe com valor de frete zero.');
        }

        if (in_array($tipoDocumento, [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value], true)
            && $this->isGuatambuMunicipio($integrado->municipio)) {
            throw new \DomainException('Não é possível solicitar CTe para integrado com município Guatambu.');
        }

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
            'integrado_cpf' => $integrado->documento,
            'documento_transporte' => $documentoTransporte,
            'destinos' => [[
                'integrado_id' => $integrado->id,
                'km_rota' => $kmRota,
                'integrado_nome' => $integrado->nome,
            ]],
            'veiculo' => $veiculo->placa,
            'created_by' => Auth::id() ?? $viagemReferencia->created_by,
            'nro_notas' => $nroNotas,
            'nfe_keys' => $this->nfeKeys($fiscalDocuments),
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

        app(CteEmailQueueService::class)->enqueue($payload);
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

        if ($valorFrete <= 0) {
            throw new \InvalidArgumentException('Não é possível criar Documento de Frete com valor zero.');
        }

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

    protected function ensureDocumentsCanGenerateFreight(Collection $fiscalDocuments): void
    {
        $cancelledNotes = $fiscalDocuments
            ->where('status', 'cancelled')
            ->pluck('numero_nota')
            ->filter()
            ->unique()
            ->implode(', ');

        if ($cancelledNotes !== '') {
            throw new \DomainException("Não é possível solicitar CT-e: NF-e cancelada ({$cancelledNotes}).");
        }
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

    /**
     * @return array<int, string>
     */
    protected function nfeKeys(Collection $fiscalDocuments): array
    {
        return $fiscalDocuments
            ->pluck('chave_nfe')
            ->map(fn (mixed $nfeKey): string => preg_replace('/\D/', '', (string) $nfeKey) ?? '')
            ->filter(fn (string $nfeKey): bool => strlen($nfeKey) === 44)
            ->unique()
            ->values()
            ->all();
    }
}
