<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class TelegramSettings extends AbstractPageSettings
{
    protected static ?string $title = 'Configurações do Telegram';

    protected static ?string $navigationLabel = 'Telegram';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected function settingName(): string
    {
        return 'config-telegram';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    public function getDefaultData(): array
    {
        return [
            'enabled' => (bool) config('services.telegram.enabled', false),
            'bot_token' => config('services.telegram.bot_token'),
            'agendamento_reminders_enabled' => (bool) config('services.telegram.agendamento_reminders_enabled', true),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Bot do Telegram')
                    ->description('O usuário precisa iniciar uma conversa com o bot antes de receber mensagens.')
                    ->columns(12)
                    ->columnSpan(8)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Integração habilitada')
                            ->default(false)
                            ->columnSpanFull(),
                        TextInput::make('bot_token')
                            ->label('Token do bot')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Crie o bot pelo @BotFather e informe o token fornecido por ele.'),
                    ]),
                Section::make('Lembretes')
                    ->columns(12)
                    ->columnSpan(4)
                    ->schema([
                        Toggle::make('agendamento_reminders_enabled')
                            ->label('Lembretes de agendamentos')
                            ->default(true)
                            ->helperText('Usa o mesmo relatório enviado pelo comando email:diario.'),
                    ]),
            ])
            ->statePath('data');
    }
}
