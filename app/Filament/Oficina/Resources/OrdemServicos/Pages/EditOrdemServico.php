<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Pages;

use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(Auth::user()->is_admin, 403);

        parent::mount($record);
    }
}
