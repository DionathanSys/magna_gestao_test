<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class ChecklistSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Checklist';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'checklist-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.checklist-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-checklist';
    }

    /**
     * Provide default values.
     *
     * @return array<string, mixed>
     */
    public function getDefaultData(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Configurações de Checklist')
                    ->columns(12)
                    ->columnSpanFull()
                    ->description('Configurações relacionadas a checklists.')
                    ->components([
                        Section::make('Itens do Checklist')
                            ->description('Adicione os itens que devem constar no checklist.')
                            ->columns(12)
                            ->columnSpan(12)
                            ->components([
                                Repeater::make('itens')
                                    ->label('Itens do Checklist')
                                    ->columns(1)
                                    ->table([
                                        TableColumn::make('item'),
                                        TableColumn::make('obrigatorio'),
                                    ])
                                    ->schema([
                                        TextInput::make('item')
                                            ->label('Item')
                                            ->required()
                                            ->columnSpanFull(),
                                        Toggle::make('obrigatorio')
                                            ->label('Obrigatório')
                                            ->default(true)
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel('Adicionar Item')
                                    ->addActionAlignment(Alignment::Start)
                                    ->columnSpanFull(),
                            ]),
                    ])
            ])
            ->statePath('data');
    }
}
