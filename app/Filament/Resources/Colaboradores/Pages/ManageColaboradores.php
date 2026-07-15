<?php

namespace App\Filament\Resources\Colaboradores\Pages;

use App\Filament\Resources\Colaboradores\ColaboradorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageColaboradores extends ManageRecords
{
    protected static string $resource = ColaboradorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => Auth::user()->is_admin),
        ];
    }
}
