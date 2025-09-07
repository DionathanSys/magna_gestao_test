<?php

namespace App\Filament\Bugio\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;

class ConfigBugioSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Configurações';

    // protected static string | BackedEnum | null $navigationIcon = 'document-plus'; // Uncomment if you want to set a custom navigation icon

    protected ?string $subheading = 'Configurações Painel Bugio'; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'config-bugio-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.config-bugio-settings';

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    protected function settingName(): string
    {
        return 'config-bugio';
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
                TextInput::make('email')
                    ->label('Email de Emissor CTe')
                    ->columnSpan(6)
                    ->email()
                    ->autocomplete(false)
                    ->required(),
                TextInput::make('email-retorno')
                    ->label('Email para Retorno')
                    ->columnSpan(6)
                    ->columnStart(1)
                    ->email()
                    ->autocomplete(false)
                    ->required(),
                Repeater::make('emails-copia')
                    ->label('Emails em Cópia')
                    ->addActionLabel('Incluir Email em Cópia')
                    ->defaultItems(1)
                    ->columnSpan(6)
                    ->columnStart(1)
                    ->simple(
                        TextInput::make('email')
                            ->columnSpanFull()
                            ->email()
                            ->required(),

                    ),
                TextInput::make('valor-quilomentro')
                    ->label('R$/Km')
                    ->columnStart(1)
                    ->columnSpan(1)
                    ->numeric()
                    ->prefix('R$')
                    ->default(0.01)
                    ->minValue(0.01)
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar')
                ->action(fn() => $this->save()),
        ];
    }
}
