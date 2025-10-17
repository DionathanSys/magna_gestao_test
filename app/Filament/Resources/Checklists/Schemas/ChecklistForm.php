<?php

namespace App\Filament\Resources\Checklists\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class ChecklistForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Info Veículo')
                            ->columns(12)
                            ->schema([
                                Select::make('veiculo_id')
                                    ->required()
                                    ->relationship('veiculo', 'placa')
                                    ->columnSpan(3),
                                DatePicker::make('data_referencia')
                                    ->label('Data Realização')
                                    ->columnSpan(3)
                                    ->default(now())
                                    ->required(),
                                TextInput::make('quilometragem')
                                    ->columnSpan(3)
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Tabs\Tab::make('Checklist')
                            ->columns(12)
                            ->schema([
                                Repeater::make('itens')
                                    ->label('Itens')
                                    ->columns(12)
                                    ->columnSpan(12)
                                    ->defaultItems(self::getCountItens())
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['item'])
                                    ->table([
                                        TableColumn::make('Item')
                                            ->weight(FontWeight::Bold)
                                            ->alignment(Alignment::Left)
                                            ->width('65%'),
                                        TableColumn::make('Status')
                                            ->alignment(Alignment::Center)
                                            ->width('1%'),
                                        TableColumn::make('Corrigido')
                                            ->width('2%'),
                                        TableColumn::make('Observações'),
                                        TableColumn::make('Obrigatório')
                                            ->width('2%'),
                                    ])
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('item')
                                            ->label('Item'),
                                        Toggle::make('status')
                                            ->label('OK')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Toggle::make('corrigido')
                                            ->label('Corrigido')
                                            ->inline(false)
                                            ->columnSpan(1),
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->columnSpan(9)
                                            ->rows(1),
                                        IconEntry::make('obrigatorio')
                                            ->label('Obrigatório')
                                            ->icon(fn(string $state): Heroicon => match ($state) {
                                                "1" => Heroicon::CheckCircle,
                                                "0" => Heroicon::XCircle,
                                            })
                                            ->color('info')
                                            ->columnSpan(1),
                                        

                                    ])
                                    ->default(fn() => self::getItens())
                                    ->deletable(false)
                                    ->addable(false),
                            ]),
                        Tabs\Tab::make('Anexos')
                            ->schema([
                                FileUpload::make('anexos')
                                    ->label('Anexos')
                                    ->multiple()
                                    ->disk('public')
                                    ->directory('checklists')
                                    ->columnSpanFull(),
                            ])
                    ])
            ]);
    }

    public static function getCountItens(): int
    {
        return count(db_config('config-checklist.itens', []));
    }

    public static function getItens(): array
    {
        $itens = collect(db_config('config-checklist.itens', []))
            ->map(fn($item) => [
                'item' => $item['item'] ?? 'Erro ao carregar item',
                'status' => false,
                'obrigatorio' => $item['obrigatorio'] ?? true,
                'observacoes' => null,
            ])
            ->toArray();

        return $itens;
    }
}
