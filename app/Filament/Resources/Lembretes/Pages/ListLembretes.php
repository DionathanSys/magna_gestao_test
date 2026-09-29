<?php

namespace App\Filament\Resources\Lembretes\Pages;

use App\Filament\Resources\Lembretes\LembreteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLembretes extends ListRecords
{
    protected static string $resource = LembreteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
