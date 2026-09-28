<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados do usuário')
                    ->columns(12)
                    ->columnSpan(8)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(6),
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpanFull()
                            ->helperText('Deixe em branco ao editar para manter a senha atual.'),
                    ]),
                Section::make('Acesso')
                    ->columns(1)
                    ->columnSpan(4)
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('Administrador')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Usuário ativo')
                            ->default(true),
                    ]),
                Section::make('Lembretes pelo Telegram')
                    ->description('O chat ID é obtido depois que o usuário inicia uma conversa com o bot.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('telegram_chat_id')
                            ->label('Chat ID do Telegram')
                            ->maxLength(64)
                            ->nullable()
                            ->columnSpan(6)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                            ->helperText('Exemplo: 123456789. Para grupos, use o chat ID do grupo.'),
                        Toggle::make('telegram_reminders_enabled')
                            ->label('Receber lembretes')
                            ->default(false)
                            ->columnSpan(6)
                            ->helperText('Só envia quando o chat ID e o bot estiverem configurados.'),
                    ]),
            ]);
    }
}
