<?php

namespace App\Services\DocumentoFrete\Queries;

use App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GetDocumentoFrete
{

    public function __construct(protected array $filters = [])
    {
    }

    protected function query(): Builder
    {
        $query = Models\DocumentoFrete::query();

        if (isset($this->filters['sem_vinculo_viagem']) && $this->filters['sem_vinculo_viagem']) {
            $query = $query->semVinculoViagem();
        }

        return $query;
    }

    public function byId(int $id): ?Models\DocumentoFrete
    {
        return $this->query()->find($id);
    }

    public function byDocumentoTransporte(int $documentoTransporte): ?Models\DocumentoFrete
    {
        Log::debug("Query byDocumentoTransporte", [
            'query' => $this->query(),
            'filters' => $this->filters,
            'documentoTransporte' => $documentoTransporte,

        ]);
        
        return $this->query()
            ->where('documento_transporte', $documentoTransporte)
            ->first();
    }
}
