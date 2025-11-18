<?php

namespace App\Filament\Pages;

use App\Enum\ClienteEnum;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViagemSettings extends AbstractPageSettings
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static ?string $title = 'Viagem';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'viagem-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.viagem-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-viagem';
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Alerta para criação de viagem')
                    ->columns(12)
                    ->columnSpan(6)
                    ->description('Configurações relacionadas a alertas de viagem.')
                    ->components([
                        Repeater::make('emails_alerta_integrados')
                            ->label('Clientes Integrados')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('cliente_integrado')
                                    ->label('Cliente Integrado')
                                    ->columnSpanFull()
                                    ->options(ClienteEnum::toSelectArray())
                                    ->required(),
                                Repeater::make('email')
                                    ->simple(TextInput::make('email')
                                        ->label('E-mail')
                                        ->columnSpanFull()
                                        ->email()
                                        ->required())
                            ])
                    ]),
            ])
            ->statePath('data');
    }
}
