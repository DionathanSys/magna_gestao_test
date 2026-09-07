<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\Abastecimentos\AbastecimentoResource;
use Filament\Tables;
use Filament\Tables\Table;

class AnaliseAbastecimentosResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-abastecimentos-resultado-periodo';

    public function getTitle(): string
    {
        return 'Abastecimentos do período';
    }

    public function table(Table $table): Table
    {
        return AbastecimentoResource::table($table)
            ->query($this->getRecord()->abastecimentos()->getQuery());
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
