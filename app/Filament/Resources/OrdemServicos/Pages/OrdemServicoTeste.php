<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
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
        $this->record = $this->resolveRecord($record)->load([
            'agendamentosPendentes.servico:id,descricao',
            'agendamentosPendentes.parceiro:id,nome',
            'planoPreventivoVinculado.planoPreventivo:id,descricao,intervalo',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\VincularServicoOrdemServicoAction::make($this->record)
                    ->size(Size::ExtraSmall),
                Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id)
                    ->size(Size::ExtraSmall),
                Actions\VincularOrdemSankhyaAction::make()
                    ->size(Size::ExtraSmall),
                Actions\EncerrarOrdemServicoAction::make()
                    ->size(Size::ExtraSmall)
                    ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl()),
                Actions\PdfOrdemServicoAction::make()
                    ->size(Size::ExtraSmall),
                DeleteAction::make('delete')
                    ->size(Size::ExtraSmall)
                    ->requiresConfirmation()
                    ->action(fn () => $this->record->delete()),
            ])->buttonGroup()->size(Size::ExtraSmall),
        ];
    }
}
