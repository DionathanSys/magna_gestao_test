<?php

namespace App\Filament\Resources\OrdemServicos\Pages;


use App\Filament\Resources\OrdemServicos\{OrdemServicoResource, Actions};
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class OrdemServicoTeste extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Ordem de Serviço';

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
            Actions\EncerrarOrdemServicoAction::make($this->record->id)
                ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl()),
            Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id),
            Actions\PdfOrdemServicoAction::make(),
        ];
    }
}
