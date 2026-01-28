<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Models\ViagemBugio;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListViagemBugios extends ListRecords
{
    protected static string $resource = ViagemBugioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            
            'emitidos' => Tab::make('Emitidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'concluido')),
            
            'em_andamento' => Tab::make('Em Andamento')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'em_andamento'))
                ->badge(ViagemBugio::where('status', 'em_andamento')->count()),
            
            'criados_ontem' => Tab::make('Criados Ontem')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', now()->subDay()->toDateString()))
                ->badge(ViagemBugio::whereDate('created_at', now()->subDay()->toDateString())->count()),
            
            'criados_hoje' => Tab::make('Criados Hoje')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', now()->toDateString()))
                ->badge(ViagemBugio::whereDate('created_at', now()->toDateString())->count()),
        ];
    }
}
