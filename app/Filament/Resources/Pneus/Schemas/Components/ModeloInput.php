<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Filament\Resources\PneuModelos\PneuModeloResource;
use App\Models\DesenhoPneu;
use App\Models\PneuModelo;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ModeloInput
{
    public static function make(): Select
    {
        return Select::make('pneu_modelo_id')
            ->label('Modelo')
            ->relationship(
                'modeloCatalogo',
                'nome',
                fn ($query, Get $get) => $query
                    ->where('ativo', true)
                    ->when(filled($get('pneu_marca_id')), fn ($modelos) => $modelos->where('pneu_marca_id', $get('pneu_marca_id')))
                    ->orderBy('nome')
            )
            ->createOptionForm(fn (Schema $schema) => PneuModeloResource::form($schema))
            ->preload()
            ->live()
            ->afterStateUpdated(function (int|string|null $state, Set $set): void {
                $modelo = PneuModelo::query()->find($state);

                if (! $modelo) {
                    $set('desenho_pneu_id', null);

                    return;
                }

                $desenhoPneuId = DesenhoPneu::query()
                    ->where('ativo', true)
                    ->where('estado_pneu', 'NOVO')
                    ->where(function ($query) use ($modelo): void {
                        $query->where('descricao', $modelo->nome)
                            ->orWhere('modelo', $modelo->nome);
                    })
                    ->value('id');

                if ($desenhoPneuId) {
                    $set('desenho_pneu_id', $desenhoPneuId);
                }
            })
            ->options(fn (Get $get): array => PneuModelo::query()
                ->where('ativo', true)
                ->when(filled($get('pneu_marca_id')), fn ($query) => $query->where('pneu_marca_id', $get('pneu_marca_id')))
                ->orderBy('nome')
                ->pluck('nome', 'id')
                ->toArray())
            ->disabled(fn (Get $get): bool => blank($get('pneu_marca_id')))
            ->helperText(fn (Get $get): ?string => blank($get('pneu_marca_id')) ? 'Selecione a marca para carregar os modelos.' : null);
    }
}
