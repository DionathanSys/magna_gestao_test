<?php

namespace App\Services\Viagem\Actions;

use App\Models\DocumentoFrete;
use App\Models\Viagem;

class AtualizarResumoViagem
{
    public function handle(Viagem|int $viagem): void
    {
        if (is_int($viagem)) {
            $viagem = Viagem::query()->find($viagem);
        }

        if (! $viagem) {
            return;
        }

        $integrados = $viagem->cargas()
            ->with('integrado:id,nome,municipio')
            ->get()
            ->pluck('integrado')
            ->filter()
            ->map(fn ($integrado) => trim(($integrado->nome ?? '') . ' - ' . ($integrado->municipio ?? '')))
            ->filter()
            ->unique()
            ->values();

        $documentos = DocumentoFrete::query()
            ->where('viagem_id', $viagem->id)
            ->get(['numero_documento', 'valor_liquido', 'parceiro_destino']);

        $viagem->updateQuietly([
            'integrados_nomes_cache' => $integrados->implode('<br>'),
            'documentos_frete_resumo_cache' => $documentos
                ->map(fn ($doc) => 'Nº ' . $doc->numero_documento . ' - R$' . number_format(($doc->valor_liquido ?? 0) / 100, 2, ',', '.'))
                ->implode('<br>'),
            'parceiro_frete_cache' => $documentos
                ->pluck('parceiro_destino')
                ->filter()
                ->implode(';<br>'),
        ]);
    }
}
