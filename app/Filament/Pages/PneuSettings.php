<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class PneuSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Pneu';

    protected string $view = 'filament.pages.pneu-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-pneu';
    }

    public function getDefaultData(): array
    {
        return [
            'alerta_km_rodizio' => 7000,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Configurações de Pneu')
                    ->columns(12)
                    ->columnSpanFull()
                    ->description('Configurações relacionadas a pneus.')
                    ->components([
                        Section::make('Marcas de Pneu')
                            ->description('Esta configuração ficou legada após a normalização em tabelas.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->schema([
                                Placeholder::make('marcas_notice')
                                    ->label('Cadastro Normalizado')
                                    ->content('Use o recurso Marcas de Pneu no menu de Pneus.'),
                            ]),
                        Section::make('Modelos de Pneu')
                            ->description('Esta configuração ficou legada após a normalização em tabelas.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->schema([
                                Placeholder::make('modelos_notice')
                                    ->label('Cadastro Normalizado')
                                    ->content('Use o recurso Modelos de Pneu no menu de Pneus.'),
                            ]),
                        Section::make('Alertas Operacionais')
                            ->description('Limites usados para alertas de rodízio e acompanhamento operacional.')
                            ->columns(12)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('alerta_km_rodizio')
                                    ->label('Km para alerta de rodízio')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(4)
                                    ->helperText('Quando o pneu atingir esse km no ciclo atual, o dashboard gera alerta de rodízio.'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
