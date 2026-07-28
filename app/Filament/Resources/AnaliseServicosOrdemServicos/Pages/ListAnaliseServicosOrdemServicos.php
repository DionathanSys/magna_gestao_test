<?php

namespace App\Filament\Resources\AnaliseServicosOrdemServicos\Pages;

use App\Filament\Resources\AnaliseServicosOrdemServicos\AnaliseServicosOrdemServicoResource;
use Filament\Resources\Pages\ListRecords;

class ListAnaliseServicosOrdemServicos extends ListRecords
{
    protected static string $resource = AnaliseServicosOrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
