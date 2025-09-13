<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\NotificacaoService as notify;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Tabs::make('Tabs')
                    ->columns(4)
                            ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Geral')
                            ->columns(4)
                            ->columnSpanFull()
                            ->schema([
                                Components\NumeroFogoInput::make(),
                                Components\MarcaInput::make(),
                                Components\ModeloInput::make(),
                                Select::make('medida')
                                    ->options([
                                        '275/80 R22.5' => '275/80 R22.5',
                                        '295/80 R22.5' => '295/80 R22.5',
                                    ])
                                    ->default('275/80 R22.5'),
                                TextInput::make('ciclo_vida')
                                    ->label('Vida')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(3),
                                TextInput::make('valor')
                                    ->label('Valor')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('R$'),
                                Components\DesenhoPneuInput::make(),
                                Select::make('status')
                                    ->options(StatusPneuEnum::toSelectArray())
                                    ->required()
                                    ->default(StatusPneuEnum::DISPONIVEL->value),
                                Select::make('local')
                                    ->options(LocalPneuEnum::toSelectArray())
                                    ->required()
                                    ->default(LocalPneuEnum::ESTOQUE_CCO->value),
                                DatePicker::make('data_aquisicao')
                                    ->label('Dt. Aquisição')
                                    ->default(now())
                                    ->maxDate(now())
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Recapagem')
                            ->columns(4)
                            ->schema([
                                Section::make('Recapagem')
                                    ->description('Registrar recapagem do pneu.')
                                    ->columns(4)
                                    ->afterHeader([
                                        Action::make('test'),
                                    ])
                                    ->columnSpanFull()
                                    ->schema([
                                        Components\RecapagemInput::make(),
                                        TextInput::make('vida_recape')
                                            ->label('Vida Recape')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(3),
                                        TextInput::make('valor_recape')
                                            ->label('Valor Recape')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->prefix('R$'),
                                        DatePicker::make('data_recape')
                                            ->label('Dt. Recape')
                                            ->maxDate(now()),
                            ]),
                    ])
                    ])
            ]);
    }
}
