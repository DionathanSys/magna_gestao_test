<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Filament\Resources\Veiculos\VeiculoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditVeiculo extends EditRecord
{
    protected static string $resource = VeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => Auth::user()->is_admin),
            ForceDeleteAction::make()
                ->visible(fn() => Auth::user()->is_admin),
            RestoreAction::make()
                ->visible(fn() => Auth::user()->is_admin),
        ];
    }
}
