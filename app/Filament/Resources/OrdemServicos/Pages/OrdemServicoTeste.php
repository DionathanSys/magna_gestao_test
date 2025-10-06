<?php

namespace App\Filament\Resources\OrdemServicos\Pages;


use App\Filament\Resources\OrdemServicos\{OrdemServicoResource, Actions};
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Size;
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
            ActionGroup::make([
                DeleteAction::make('delete')
                ->requiresConfirmation()
                ->action(fn () => $this->record->delete()),
                Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id),
            ])->label('Ações')->button()->size(Size::ExtraSmall),

            Actions\EncerrarOrdemServicoAction::make($this->record->id)
                ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl()),

            Actions\PdfOrdemServicoAction::make(),
        ];
    }
}
