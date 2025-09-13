<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PneuSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Pneu';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'pneu-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.pneu-settings';

    protected function settingName(): string
    {
        return 'config-pneu';
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
                Section::make('Configurações de Pneu')
                    ->columnSpanFull()
                    ->description('Configurações relacionadas a pneus.')
                    ->components([
                        Repeater::make('marcas_pneu')
                            ->label('Marcas de Pneu')
                            ->columns(12)
                            ->schema([
                                TextInput::make('marca')
                                    ->label('Marca')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(4)
                            ->addActionLabel('Adicionar Marca de Pneu')
                            ->collapsible()
                            ->collapsed(),
                        Repeater::make('modelos_pneu')
                            ->label('Modelos de Pneu')
                            ->columns(12)
                            ->schema([
                                TextInput::make('modelo')
                                    ->label('Modelo')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(4)
                            ->addActionLabel('Adicionar Modelo de Pneu')
                            ->collapsible()
                            ->collapsed(),
                    ]),
            ])
            ->statePath('data');
    }
}
