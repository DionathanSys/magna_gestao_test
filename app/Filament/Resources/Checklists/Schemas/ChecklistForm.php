<?php

namespace App\Filament\Resources\Checklists\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Select::make('veiculo_id')
                    ->required()
                    ->relationship('veiculo', 'placa')
                    ->columnSpan(3),
                DatePicker::make('data_referencia')
                    ->label('Data Realização')
                    ->columnSpan(3)
                    ->required(),
                TextInput::make('quilometragem')
                    ->columnSpan(3)
                    ->required()
                    ->numeric()
                    ->default(0),
                FileUpload::make('anexos')
                    ->columnSpanFull(),
                Repeater::make('itens_verificados')
                    ->label('Itens Verificados')
                    ->columnSpan(6)
                    ->schema([
                        TextInput::make('item')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([
                                'ok'       => 'Ok',
                                'pendente' => 'Pendente',
                                'falha'    => 'Falha',
                            ])
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(1),
            ]);
    }
}
