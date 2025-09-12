<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Models\PneuPosicaoVeiculo;
use App\Services;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class TrocarPneuAction
{
    protected Services\Pneus\MovimentarPneuService $movimentarPneuService;

    public function __construct()
    {
        $this->movimentarPneuService = new Services\Pneus\MovimentarPneuService();
    }

    public static function make(): Action
    {
        return Action::make('trocar-pneu')
            ->icon('heroicon-o-arrows-right-left')
            ->iconButton()
            ->tooltip('Substituir Pneu')
            ->visible(fn($record) => ! $record->pneu_id == null)
            ->modalWidth(Width::ExtraLarge)
            ->schema(fn(Schema $schema) => $schema
                ->columns(4)
                ->schema([
                    // PneuResource::getMotivoMovimentacaoFormField()
                    //     ->label('Motivo Movimentação'),
                    // PneuResource::getPneuDisponivelFormField(),
                    // PneuResource::getDataInicialOrdemFormField()
                    //     ->label('Dt. Movimentação'),
                    // PneuResource::getKmInicialOrdemFormField()
                    //     ->label('KM Movimentação'),
                    // PneuResource::getObservacaoFormField(),
                ]))
            ->action(fn(array $data, PneuPosicaoVeiculo $record) => $this->movimentarPneuService->trocarPneu($record, $data));
    }
}
