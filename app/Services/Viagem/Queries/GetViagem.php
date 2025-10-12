<?php

namespace App\Services\Viagem\Queries;

use App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GetViagem
{
    public function __construct(protected array $filters = [])
    {
    }

    protected function query(): Builder
    {
        $query = Models\Viagem::query();

        return $query;
    }

    public function byId(int $id): ?Models\Viagem
    {
        return $this->query()->find($id);
    }

    public function byDocumentoTransporte(int $documentoTransporte): ?Models\Viagem
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
