<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Services;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class VincularPneuAction
{
    protected Services\Pneus\MovimentarPneuService $movimentarPneuService;

    public function __construct()
    {
        $this->movimentarPneuService = new Services\Pneus\MovimentarPneuService();
    }

    public static function make(): Action
    {
        return Action::make('vincular-pneu')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('info')
            ->iconButton()
            ->tooltip('Vincular Pneu')
            ->visible(fn($record) => $record->pneu_id == null)
            ->modalWidth(Width::ExtraLarge)
            ->schema(fn(Schema $schema) => $schema
                ->columns(4)
                ->schema([
                    // PneuResource::getPneuDisponivelFormField()
                    //     ->label('Pneu Disponível')
                    //     ->columnSpan(3),
                    // PneuResource::getDataInicialOrdemFormField()
                    //     ->label('Dt. Movimentação')
                    //     ->columnStart(1)
                    //     ->columnSpan(2),
                    // PneuResource::getKmInicialOrdemFormField()
                    //     ->label('KM Movimentação')
                    //     ->columnSpan(2),
                ]))
            ->action(fn($record, array $data) => $this->movimentarPneuService->aplicarPneu($record, $data));
    }
}
