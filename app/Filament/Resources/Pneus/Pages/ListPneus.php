<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Enum\Pneu\LocalPneuEnum;
use App\Filament\Resources\Pneus\PneuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListPneus extends ListRecords
{
    protected static string $resource = PneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Pneu')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Todos' => Tab::make(),
            'Estoque' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('local', LocalPneuEnum::ESTOQUE_CCO)),
            'Frota' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('local', LocalPneuEnum::FROTA)),
            'Outros' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNotIn('local', [LocalPneuEnum::ESTOQUE_CCO, LocalPneuEnum::FROTA])),
            'Est./Frota' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('local', [LocalPneuEnum::ESTOQUE_CCO, LocalPneuEnum::FROTA])),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Estoque';
    }
}
