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
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Illuminate\Database\Eloquent\Builder;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
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
                Section::make('Recapagem')
                    ->description('Registrar recapagem do pneu.')
                    ->visibleOn('create')
                    ->columns(12)
                    ->afterHeader([
                        Action::make('Registrar Recapagem'),
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('pneu_id')
                            ->label('Pneu')
                            ->columnSpan(2)
                            ->required(),
                        DatePicker::make('data_recapagem')
                            ->date('d/m/Y')
                            ->columnSpan(3)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->maxDate(now())
                            ->required(),
                        TextInput::make('valor')
                            ->label('Valor')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(0)
                            ->prefix('R$'),
                        Components\DesenhoPneuInput::make()
                            ->columnSpan(4),
                        
                    ])

            ]);
    }
}
