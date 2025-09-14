<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Filament\Resources\Pneus\PneuResource;
use App\Livewire\PneuResource as LivewirePneuResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPneu extends ViewRecord
{
    protected static string $resource = PneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    

    protected function getHeaderWidgets(): array
    {
        return [
            LivewirePneuResource::class,
        ];
    }
}
