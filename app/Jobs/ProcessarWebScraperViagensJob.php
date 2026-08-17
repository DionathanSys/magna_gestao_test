<?php

namespace App\Jobs;

use App\Models\Veiculo;
use App\Services\Integrado\IntegradoDestinoService;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\Viagem\ViagemService;
use App\Services\WebScraper\WebScraperViagemErrorCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarWebScraperViagensJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $loteId,
        private array $viagens,
        private string $requestId,
    ) {}

    public function handle(WebScraperViagemErrorCache $errorCache): void
    {
        $resumo = [
            'criadas' => 0,
            'atualizadas' => 0,
            'ignoradas_conferidas' => 0,
            'falhas' => 0,
        ];

        Log::info('Iniciando processamento de viagens WebScraper', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'total_viagens' => count($this->viagens),
        ]);

        foreach ($this->viagens as $index => $payload) {
            $numeroViagem = $payload['numero_viagem'] ?? null;

            try {
                $data = $this->normalizarPayload($payload);
                $service = new ViagemService;
                $viagem = $service->updateOrCreate($data);

                if ($service->hasError() || ! $viagem) {
                    $resumo['falhas']++;
                    $this->registrarFalha($errorCache, $index, $payload, $service->getMessageUser() ?: 'Falha ao criar/atualizar viagem.');

                    continue;
                }

                $serviceData = $service->getData();
                $acao = (string) ($serviceData['acao'] ?? 'processada');

                (new IntegradoDestinoService)->vincularCarga($viagem, $payload['destino'] ?? null);

                match ($acao) {
                    'criada' => $resumo['criadas']++,
                    'atualizada' => $resumo['atualizadas']++,
                    'ignorada_conferida' => $resumo['ignoradas_conferidas']++,
                    default => null,
                };

                Log::info('Viagem WebScraper processada', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'request_id' => $this->requestId,
                    'lote_id' => $this->loteId,
                    'index' => $index,
                    'acao' => $acao,
                    'mensagem' => $service->getMessage(),
                    'viagem_id' => $viagem->id,
                    'numero_viagem' => $viagem->numero_viagem,
                    'payload_resumo' => $this->snapshotPayload($payload),
                    'campos_salvos' => $this->snapshotViagem($viagem->fresh()),
                ]);

                if ($acao !== 'ignorada_conferida') {
                    $this->registrarDiferencasPersistencia($index, $data, $viagem->fresh());
                }
            } catch (Throwable $exception) {
                $resumo['falhas']++;
                $this->registrarFalha($errorCache, $index, $payload, $exception->getMessage());

                Log::error('Excecao ao processar viagem WebScraper', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'request_id' => $this->requestId,
                    'lote_id' => $this->loteId,
                    'index' => $index,
                    'numero_viagem' => $numeroViagem,
                    'error' => $exception->getMessage(),
                    'payload' => $payload,
                ]);
            }
        }

        Log::info('Processamento de viagens WebScraper finalizado', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'total_viagens' => count($this->viagens),
            'resumo' => $resumo,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        app(WebScraperViagemErrorCache::class)->add([
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'numero_viagem' => null,
            'index' => null,
            'erro' => 'Job falhou antes de concluir: '.($exception?->getMessage() ?? 'Erro desconhecido'),
            'payload' => ['total_viagens' => count($this->viagens)],
        ]);

        Log::error('Falha geral no job de viagens WebScraper', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    private function normalizarPayload(array $payload): array
    {
        $expectedKeys = [
            'placa',
            'veiculo_id',
            'unidade_negocio',
            'cliente',
            'numero_viagem',
            'numero_interno',
            'documento_transporte',
            'km_rodado',
            'km_pago',
            'data_competencia',
            'data_inicio',
            'data_fim',
            'total_destinos',
            'conferido',
            'ignorar',
            'possui_pendencia',
            'pendencias',
            'motorista1',
            'motorista2',
            'destino',
        ];

        $ignoredKeys = array_values(array_diff(array_keys($payload), $expectedKeys));

        if ($ignoredKeys !== []) {
            Log::warning('Payload WebScraper contem campos sem mapeamento para viagens', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'request_id' => $this->requestId,
                'lote_id' => $this->loteId,
                'numero_viagem' => $payload['numero_viagem'] ?? null,
                'campos_ignorados' => $ignoredKeys,
            ]);
        }

        $data = Arr::only($payload, [
            'veiculo_id',
            'unidade_negocio',
            'cliente',
            'numero_viagem',
            'numero_interno',
            'documento_transporte',
            'km_rodado',
            'km_pago',
            'data_competencia',
            'data_inicio',
            'data_fim',
            'total_destinos',
            'conferido',
            'ignorar',
            'possui_pendencia',
            'pendencias',
            'motorista1',
            'motorista2',
        ]);

        if (empty($data['veiculo_id'])) {
            $data['veiculo_id'] = $this->buscarVeiculoIdPorPlaca($payload['placa'] ?? null);
        }

        if (! array_key_exists('conferido', $data)) {
            $data['conferido'] = false;
        }

        if (! array_key_exists('ignorar', $data)) {
            $data['ignorar'] = false;
        }

        if (! array_key_exists('possui_pendencia', $data)) {
            $data['possui_pendencia'] = false;
        }

        foreach (['km_rodado', 'km_pago'] as $campoKm) {
            if (! array_key_exists($campoKm, $data) || $data[$campoKm] === null || $data[$campoKm] === '') {
                Log::warning('Payload WebScraper recebido sem valor de KM; normalizando para zero', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'request_id' => $this->requestId,
                    'lote_id' => $this->loteId,
                    'numero_viagem' => $payload['numero_viagem'] ?? null,
                    'campo' => $campoKm,
                    'valor_original' => $data[$campoKm] ?? null,
                ]);

                $data[$campoKm] = 0;
            }
        }

        return $data;
    }

    private function registrarDiferencasPersistencia(int $index, array $data, ?object $viagem): void
    {
        if (! $viagem) {
            return;
        }

        $diferencas = [];

        foreach ($data as $campo => $valor) {
            if (! in_array($campo, [
                'veiculo_id',
                'unidade_negocio',
                'cliente',
                'numero_viagem',
                'numero_interno',
                'documento_transporte',
                'km_rodado',
                'km_pago',
                'data_competencia',
                'data_inicio',
                'data_fim',
                'total_destinos',
                'conferido',
                'ignorar',
                'possui_pendencia',
                'pendencias',
                'motorista1',
                'motorista2',
            ], true)) {
                continue;
            }

            if (! array_key_exists($campo, $viagem->getAttributes())) {
                continue;
            }

            $salvo = $viagem->getAttribute($campo);

            if ($this->normalizarComparacao($valor) !== $this->normalizarComparacao($salvo)) {
                $diferencas[$campo] = [
                    'payload' => $valor,
                    'salvo' => $salvo,
                ];
            }
        }

        if ($diferencas === []) {
            return;
        }

        Log::warning('Diferenca entre payload WebScraper normalizado e viagem persistida', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'index' => $index,
            'viagem_id' => $viagem->id,
            'numero_viagem' => $viagem->numero_viagem,
            'diferencas' => $diferencas,
        ]);
    }

    private function snapshotViagem(?object $viagem): array
    {
        if (! $viagem) {
            return [];
        }

        return Arr::only($viagem->toArray(), [
            'id',
            'veiculo_id',
            'unidade_negocio',
            'cliente',
            'numero_viagem',
            'numero_interno',
            'documento_transporte',
            'km_rodado',
            'km_pago',
            'data_competencia',
            'data_inicio',
            'data_fim',
            'total_destinos',
            'conferido',
            'ignorar',
            'possui_pendencia',
            'pendencias',
            'motorista1',
            'motorista2',
        ]);
    }

    private function snapshotPayload(array $payload): array
    {
        return Arr::only($payload, [
            'numero_viagem',
            'placa',
            'veiculo_id',
            'unidade_negocio',
            'data_competencia',
            'data_inicio',
            'data_fim',
            'conferido',
            'ignorar',
            'possui_pendencia',
            'destino',
        ]);
    }

    private function normalizarComparacao(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            ksort($value);

            return json_encode($value);
        }

        if (is_numeric($value)) {
            return (string) +$value;
        }

        return $value === null ? null : (string) $value;
    }

    private function buscarVeiculoIdPorPlaca(?string $placa): int
    {
        $placaNormalizada = DocumentIdentity::normalizePlate($placa);

        if ($placaNormalizada === null) {
            throw new \InvalidArgumentException('Placa nao informada ou invalida.');
        }

        $veiculo = Veiculo::query()
            ->select('id', 'placa')
            ->where('is_active', true)
            ->where(function ($query) use ($placa, $placaNormalizada): void {
                $query->where('placa', trim((string) $placa))
                    ->orWhere('placa', $placaNormalizada);
            })
            ->first();

        if (! $veiculo) {
            throw new \InvalidArgumentException("Veiculo ativo nao encontrado para a placa {$placaNormalizada}.");
        }

        return (int) $veiculo->id;
    }

    private function registrarFalha(WebScraperViagemErrorCache $errorCache, int $index, array $payload, string $erro): void
    {
        $errorCache->add([
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'numero_viagem' => $payload['numero_viagem'] ?? null,
            'index' => $index,
            'erro' => $erro,
            'payload' => $payload,
        ]);

        Log::warning('Falha acumulada para notificacao WebScraper', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'request_id' => $this->requestId,
            'lote_id' => $this->loteId,
            'index' => $index,
            'numero_viagem' => $payload['numero_viagem'] ?? null,
            'erro' => $erro,
        ]);
    }
}
