<?php

namespace App\Filament\Resources\DesenhoPneus\Pages;

use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDesenhoPneus extends ManageRecords
{
    protected static string $resource = DesenhoPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Desenho de Pneu'),
        ];
    }
}
