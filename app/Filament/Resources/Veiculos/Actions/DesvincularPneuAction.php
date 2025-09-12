<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Services;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class DesvincularPneuAction
{
    protected Services\Pneus\MovimentarPneuService $movimentarPneuService;

    public function __construct()
    {
        $this->movimentarPneuService = new Services\Pneus\MovimentarPneuService();
    }

    public static function make(): Action
    {
        return Action::make('desvincular-pneu')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Desvincular Pneu')
                    ->visible(fn($record) => ! $record->pneu_id == null)
                    ->modalWidth(Width::ExtraLarge)
                    ->schema(fn(Schema $schema) => $schema
                        ->columns(4)
                        ->schema([
                            // PneuResource::getMotivoMovimentacaoFormField()
                            //     ->columnSpan(3),
                            // PneuResource::getSulcoFormField()
                            //     ->columnSpan(1),
                            // PneuResource::getDataFinalOrdemFormField()
                            //     ->label('Dt. Movimentação')
                            //     ->columnSpan(2),
                            // PneuResource::getKmFinalOrdemFormField()
                            //     ->label('KM Movimentação')
                            //     ->columnSpan(2),
                            // PneuResource::getObservacaoFormField(),
                        ]))
                        ->action(fn($record, array $data) => $this->movimentarPneuService->removerPneu($record, $data));
    }
}
