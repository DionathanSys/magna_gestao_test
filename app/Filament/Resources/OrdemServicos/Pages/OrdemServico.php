<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class OrdemServico extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrdemServicoResource::class;

    protected string $view = 'filament.resources.ordem-servicos.pages.ordem-servico';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
