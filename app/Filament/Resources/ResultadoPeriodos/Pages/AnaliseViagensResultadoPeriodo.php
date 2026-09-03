<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\Viagems\ViagemResource;
use Filament\Tables;
use Filament\Tables\Table;

class AnaliseViagensResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-viagens-resultado-periodo';

    public function getTitle(): string
    {
        return 'Viagens do período';
    }

    public function table(Table $table): Table
    {
        return ViagemResource::table($table)
            ->query($this->getRecord()->viagens());
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
