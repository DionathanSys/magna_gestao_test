<?php

namespace App\Filament\Resources\OrdemServicos\Pages;


use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Resources\OrdemServicos\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class OrdemServicoTeste extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrdemServicoResource::class;

    protected string $view = 'filament.resources.ordem-servicos.pages.ordem-servico-teste';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make('delete')
                ->requiresConfirmation()
                ->action(fn () => $this->record->delete()),
            Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id),
        ];
    }
}
