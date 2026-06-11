<?php

namespace App\Filament\Resources\CteEmailRequests\Pages;

use App\Filament\Resources\CteEmailRequests\CteEmailRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCteEmailRequests extends ListRecords
{
    protected static string $resource = CteEmailRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
