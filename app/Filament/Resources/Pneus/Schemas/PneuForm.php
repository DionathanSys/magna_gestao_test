<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Filament\Resources\Pneus\Actions;
use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Components\NumeroFogoInput::make()
                    ->columnSpan(5),
                TextInput::make('ciclo_vida')
                    ->label('Vida')
                    ->columnSpan(2)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(3),
                TextInput::make('valor')
                    ->columnSpan(3)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('R$'),
                Components\MarcaInput::make()
                    ->columnStart(1)
                    ->columnSpan(4),
                Components\ModeloInput::make()
                    ->columnSpan(4),
                Select::make('medida')
                    ->columnSpan(4)
                    ->options([
                        '275/80 R22.5' => '275/80 R22.5',
                        '295/80 R22.5' => '295/80 R22.5',
                    ])
                    ->default('275/80 R22.5'),

                Components\DesenhoPneuInput::make()
                    ->columnSpan(4),
                Select::make('status')
                    ->columnStart(1)
                    ->columnSpan(4)
                    ->options(StatusPneuEnum::toSelectArray())
                    ->required()
                    ->default(StatusPneuEnum::DISPONIVEL->value),
                Select::make('local')
                    ->columnSpan(4)
                    ->options(LocalPneuEnum::toSelectArray())
                    ->required()
                    ->default(LocalPneuEnum::ESTOQUE_CCO->value),
                DatePicker::make('data_aquisicao')
                    ->label('Dt. Aquisição')
                    ->columnSpan(3)
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                Section::make('Recapagem')
                    ->description('Registrar recapagem do pneu.')
                    ->visibleOn('create')
                    ->columns(12)
                    ->afterHeader([
                        //TODO: Incluir limpeza do form, ou action para resetar
                        fn(Get $get) => dd($get('recap')),
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('recap.pneu_id')
                            ->label('Pneu')
                            ->columnSpan(2),
                        DatePicker::make('recap.data_recapagem')
                            ->date('d/m/Y')
                            ->columnSpan(3)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('recap.valor_recapagem')
                            ->label('Valor')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(0)
                            ->prefix('R$'),
                        Select::make('recap.desenho_pneu_id_recapagem')
                            ->label('Desenho Borracha')
                            ->relationship('desenhoPneu', 'descricao', fn($query) => $query->where('estado_pneu', 'RECAPADO'))
                            ->searchable()
                            ->preload()
                            // ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
                            ->columnSpan(4),

                    ])

            ]);
    }

}
