<?php

namespace App\Console\Commands;

use App\Models\CteEmailRequest;
use App\Models\ReceivedFiscalDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillCteEmailRequestNfeKeys extends Command
{
    protected $signature = 'cte:backfill-request-nfe-keys
        {--dry-run : Apenas mostra as solicitacoes que seriam atualizadas}
        {--chunk=200 : Quantidade de solicitacoes processadas por lote}
        {--request-id= : Processa somente uma solicitacao}';

    protected $description = 'Preenche as chaves de NF-e das solicitacoes de CTe existentes para correlacionar retornos XML';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $requestId = $this->option('request-id');
        $scanned = 0;
        $updated = 0;
        $withoutKeys = 0;

        CteEmailRequest::query()
            ->whereNull('nfe_keys')
            ->when($requestId, fn ($query) => $query->whereKey((int) $requestId))
            ->with('viagem.attachments.receivedFiscalDocument')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $requests) use ($dryRun, &$scanned, &$updated, &$withoutKeys): void {
                foreach ($requests as $request) {
                    $scanned++;
                    $nfeKeys = $this->resolveNfeKeys($request);

                    if ($nfeKeys === []) {
                        $withoutKeys++;

                        if ($dryRun) {
                            $this->line("[dry-run] solicitacao={$request->id} sem chaves de NF-e identificadas");
                        }

                        continue;
                    }

                    $updated++;

                    if ($dryRun) {
                        $this->line("[dry-run] solicitacao={$request->id} chaves=".count($nfeKeys));

                        continue;
                    }

                    $request->update(['nfe_keys' => $nfeKeys]);
                }
            });

        $this->info(sprintf(
            'Backfill concluido. Solicitacoes analisadas: %d. %s: %d. Sem chaves identificadas: %d.',
            $scanned,
            $dryRun ? 'Que seriam atualizadas' : 'Atualizadas',
            $updated,
            $withoutKeys,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveNfeKeys(CteEmailRequest $request): array
    {
        $keysFromViagem = $request->viagem?->attachments
            ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->chave_nfe)
            ->all() ?? [];

        $noteNumbers = collect($request->payload['nro_notas'] ?? [])
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->unique()
            ->values();

        $keysFromPayload = $noteNumbers->isEmpty()
            ? []
            : ReceivedFiscalDocument::query()
                ->whereIn('numero_nota', $noteNumbers)
                ->whereNotNull('chave_nfe')
                ->get(['numero_nota', 'chave_nfe'])
                ->groupBy('numero_nota')
                ->map(function (Collection $documents): ?string {
                    $keys = $this->normalizeNfeKeys($documents->pluck('chave_nfe')->all());

                    return count($keys) === 1 ? $keys[0] : null;
                })
                ->filter()
                ->values()
                ->all();

        return $this->normalizeNfeKeys([...$keysFromViagem, ...$keysFromPayload]);
    }

    /**
     * @param  array<int, mixed>  $nfeKeys
     * @return array<int, string>
     */
    protected function normalizeNfeKeys(array $nfeKeys): array
    {
        return collect($nfeKeys)
            ->map(fn (mixed $nfeKey): string => preg_replace('/\D/', '', (string) $nfeKey) ?? '')
            ->filter(fn (string $nfeKey): bool => strlen($nfeKey) === 44)
            ->unique()
            ->values()
            ->all();
    }
}
