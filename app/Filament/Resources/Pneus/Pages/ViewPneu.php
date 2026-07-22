<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Filament\Resources\Pneus\Actions\EnviarRecapagemPneuAction;
use App\Filament\Resources\Pneus\Actions\ReceberRecapagemPneuAction;
use App\Filament\Resources\Pneus\Actions\RetornarConsertoPneuAction;
use App\Filament\Resources\Pneus\Actions\ReverterRecapagemPneuAction;
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
            RetornarConsertoPneuAction::make(),
            EnviarRecapagemPneuAction::make(),
            ReceberRecapagemPneuAction::make(),
            ReverterRecapagemPneuAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LivewirePneuResource::class,
        ];
    }
}
