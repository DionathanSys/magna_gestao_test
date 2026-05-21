<?php

namespace App\Filament\Resources\PneuInspecoes\Schemas;

use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Models\Parceiro;
use App\Models\Pneu;
use App\Models\PneuCiclo;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PneuInspecaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components(static::getComponents());
    }

    public static function getComponents(): array
    {
        return [
            Group::make([
                Select::make('pneu_id')
                    ->label('Nº de Fogo')
                    ->options(Pneu::query()->orderBy('numero_fogo')->pluck('numero_fogo', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('pneu_ciclo_id')
                    ->label('Ciclo')
                    ->options(fn (Get $get): array => PneuCiclo::query()
                        ->where('pneu_id', $get('pneu_id'))
                        ->orderByDesc('numero')
                        ->get()
                        ->mapWithKeys(fn (PneuCiclo $ciclo) => [$ciclo->id => 'Ciclo '.$ciclo->numero.' - '.$ciclo->status->value])
                        ->toArray())
                    ->searchable()
                    ->preload(),
            ]),
            Group::make([
                Select::make('tipo')
                    ->label('Tipo')
                    ->options(TipoInspecaoPneuEnum::toSelectArray())
                    ->required(),
                Select::make('resultado')
                    ->label('Resultado')
                    ->options(ResultadoInspecaoPneuEnum::toSelectArray())
                    ->required(),
                DatePicker::make('data_inspecao')
                    ->label('Dt. Inspeção')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
            ])
            ->columns(3)
            ->columnSpanFull(),
          Group::make([
                  Select::make('pneu_posicao_veiculo_id')
                ->label('Posição Aplicada')
                ->options(fn (Get $get): array => PneuPosicaoVeiculo::query()
                    ->where('pneu_id', $get('pneu_id'))
                    ->when($get('veiculo_id'), fn ($query, $veiculoId) => $query->where('veiculo_id', $veiculoId))
                    ->orderBy('sequencia')
                    ->get()
                    ->mapWithKeys(fn (PneuPosicaoVeiculo $posicao) => [
                        $posicao->id => trim(($posicao->veiculo?->placa ?? 'Sem veículo').' - '.$posicao->eixo.' eixo / '.$posicao->posicao),
                    ])
                    ->toArray())
                ->searchable(),
            TextInput::make('km_referencia')
                ->label('KM Referência')
                ->numeric(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Group::make([
                TextInput::make('sulco_interno')
                    ->label('Sulco Interno')
                    ->numeric(),
                TextInput::make('sulco_centro')
                    ->label('Sulco Centro')
                    ->numeric(),
                TextInput::make('sulco_externo')
                    ->label('Sulco Externo')
                    ->numeric(),
            ])
            ->columns(3)
            ->columnSpanFull(),
            Select::make('apto_recapagem')
                ->label('Apto Recapagem')
                ->options([
                    1 => 'SIM',
                    0 => 'NAO',
                ])
                ->columnSpan(2),
            Textarea::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
            FileUpload::make('anexos')
                ->label('Anexos')
                ->image()
                ->multiple()
                ->openable()
                ->downloadable()
                ->panelLayout('grid')
                ->disk('local')
                ->directory('pneus/inspecoes')
                ->visibility('private')
                ->columnSpanFull(),
        ];
    }
}
