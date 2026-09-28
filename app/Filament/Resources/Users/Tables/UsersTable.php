<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\TelegramService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                IconColumn::make('is_admin')
                    ->label('Administrador')
                    ->boolean(),
                TextColumn::make('telegram_chat_id')
                    ->label('Chat ID')
                    ->placeholder('Não configurado')
                    ->toggleable(),
                IconColumn::make('telegram_reminders_enabled')
                    ->label('Lembretes Telegram')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('testarTelegram')
                    ->label('Testar Telegram')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => (bool) Auth::user()?->is_admin && filled($record->telegram_chat_id))
                    ->action(function (User $record): void {
                        try {
                            app(TelegramService::class)->sendMessage(
                                $record->telegram_chat_id,
                                'Teste de integração do Telegram enviado pelo Magna Gestão.',
                            );

                            Notification::make()
                                ->success()
                                ->title('Mensagem enviada')
                                ->body('O Telegram recebeu a mensagem de teste.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Falha no envio')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
