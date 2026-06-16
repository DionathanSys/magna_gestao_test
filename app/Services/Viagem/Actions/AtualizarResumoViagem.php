<?php

namespace App\Services\Viagem\Actions;

use App\Models\DocumentoFrete;
use App\Models\Viagem;
use Illuminate\Support\Facades\Schema;

class AtualizarResumoViagem
{
    private array $cacheColumns = [
        'integrados_json',
        'integrados_nomes_cache',
        'documentos_frete_resumo_cache',
        'parceiro_frete_cache',
    ];

    private array $availableColumns = [];

    public function __construct()
    {
        $this->availableColumns = array_filter($this->cacheColumns, fn ($col) => Schema::hasColumn('viagens', $col));
    }

    public function handle(Viagem|int $viagem): void
    {
        if (is_int($viagem)) {
            $viagem = Viagem::query()->find($viagem);
        }

        if (! $viagem) {
            return;
        }

        $updateData = [];

        if (in_array('integrados_json', $this->availableColumns) || in_array('integrados_nomes_cache', $this->availableColumns)) {
            $integrados = $viagem->cargas()
                ->with('integrado:id,codigo,nome,municipio')
                ->get()
                ->pluck('integrado')
                ->filter()
                ->unique()
                ->values();

            $integradosJson = $integrados
                ->map(fn ($integrado) => [
                    'id' => $integrado->id,
                    'codigo' => $integrado->codigo,
                    'nome' => $integrado->nome,
                    'municipio' => $integrado->municipio,
                ])
                ->values()
                ->all();

            if (in_array('integrados_json', $this->availableColumns)) {
                $updateData['integrados_json'] = $integradosJson;
            }

            if (in_array('integrados_nomes_cache', $this->availableColumns)) {
                $integradosResumo = collect($integradosJson)
                    ->map(fn ($integrado) => trim(($integrado['nome'] ?? '').' - '.($integrado['municipio'] ?? '')))
                    ->filter()
                    ->unique()
                    ->values();

                $updateData['integrados_nomes_cache'] = $integradosResumo->implode('<br>');
            }
        }

        if (in_array('documentos_frete_resumo_cache', $this->availableColumns) || in_array('parceiro_frete_cache', $this->availableColumns)) {
            $documentos = DocumentoFrete::query()
                ->where('viagem_id', $viagem->id)
                ->get(['numero_documento', 'valor_liquido', 'parceiro_destino']);

            if (in_array('documentos_frete_resumo_cache', $this->availableColumns)) {
                $updateData['documentos_frete_resumo_cache'] = $documentos
                    ->map(fn ($doc) => 'Nº '.$doc->numero_documento.' - R$'.number_format($doc->valor_liquido ?? 0, 2, ',', '.'))
                    ->implode('<br>');
            }

            if (in_array('parceiro_frete_cache', $this->availableColumns)) {
                $updateData['parceiro_frete_cache'] = $documentos
                    ->pluck('parceiro_destino')
                    ->filter()
                    ->implode(';<br>');
            }
        }

        if ($updateData !== []) {
            $viagem->updateQuietly($updateData);
        }
    }
}
