<?php

namespace App\Jobs;

use App\Models\Veiculo;
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
                    $this->registrarFalha($errorCache, $index, $payload, $service->getMessageUser() ?: 'Falha ao criar/atualizar viagem.');

                    continue;
                }

                Log::info('Viagem WebScraper processada com sucesso', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'request_id' => $this->requestId,
                    'lote_id' => $this->loteId,
                    'index' => $index,
                    'viagem_id' => $viagem->id,
                    'numero_viagem' => $viagem->numero_viagem,
                ]);
            } catch (Throwable $exception) {
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

        return $data;
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
