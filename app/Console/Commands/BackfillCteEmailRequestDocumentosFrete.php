<?php

namespace App\Console\Commands;

use App\Models\CteEmailRequest;
use App\Models\DocumentoFrete;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillCteEmailRequestDocumentosFrete extends Command
{
    protected $signature = 'cte:backfill-request-documentos-frete
        {--dry-run : Apenas mostra os documentos que seriam vinculados}
        {--chunk=200 : Quantidade de solicitacoes processadas por lote}
        {--request-id= : Processa somente uma solicitacao}';

    protected $description = 'Vincula documentos de frete ja criados aos respectivos retornos de solicitacao CTe';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $requestId = $this->option('request-id');
        $linked = 0;
        $ambiguous = 0;

        CteEmailRequest::query()
            ->when($requestId, fn ($query) => $query->whereKey((int) $requestId))
            ->with('messages.incomingEmail.attachments')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $requests) use ($dryRun, &$linked, &$ambiguous): void {
                foreach ($requests as $request) {
                    foreach ($this->documentNumbersFromReturnAttachments($request) as $documentNumber) {
                        $documents = DocumentoFrete::query()
                            ->whereNull('cte_email_request_id')
                            ->where('viagem_id', $request->viagem_id)
                            ->where('documento_transporte', $request->documento_transporte)
                            ->where('numero_documento', $documentNumber)
                            ->get();

                        if ($documents->count() !== 1) {
                            $ambiguous += $documents->isNotEmpty() ? 1 : 0;

                            continue;
                        }

                        $document = $documents->first();
                        $linked++;

                        if ($dryRun) {
                            $this->line("[dry-run] documento_frete={$document->id} solicitacao={$request->id}");

                            continue;
                        }

                        $document->update(['cte_email_request_id' => $request->id]);
                    }
                }
            });

        $this->info(sprintf(
            'Backfill concluido. Documentos %s: %d. Casos ambiguos ignorados: %d.',
            $dryRun ? 'que seriam vinculados' : 'vinculados',
            $linked,
            $ambiguous,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function documentNumbersFromReturnAttachments(CteEmailRequest $request): array
    {
        return $request->messages
            ->flatMap(fn ($message) => $message->incomingEmail?->attachments ?? [])
            ->map(fn ($attachment): array => $attachment->metadata ?? [])
            ->filter(fn (array $metadata): bool => ($metadata['cte_email_request_id'] ?? null) === $request->id
                && ($metadata['cte_return'] ?? null) === 'document_created')
            ->pluck('numero_documento')
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
