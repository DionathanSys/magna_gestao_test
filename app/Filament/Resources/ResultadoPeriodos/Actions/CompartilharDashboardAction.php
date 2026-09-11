<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardShareService;
use Filament\Actions\Action as NotificationAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CompartilharDashboardAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('compartilhar_dashboard')
            ->label('Compartilhar dashboard')
            ->icon(Heroicon::Share)
            ->requiresConfirmation()
            ->modalHeading('Compartilhar dashboard de resultados')
            ->modalDescription(fn (Collection $records): string => 'O link terá acesso somente aos '.$records->count().' resultado(s) selecionado(s).')
            ->schema([
                TextInput::make('destinatario_nome')
                    ->label('Nome do destinatário')
                    ->helperText('Será exibido no dashboard para identificar a pessoa autorizada.')
                    ->required()
                    ->maxLength(120),
                TextInput::make('destinatario_email')
                    ->label('E-mail do destinatário')
                    ->email()
                    ->maxLength(255),
                Select::make('validade_horas')
                    ->label('Validade do link')
                    ->options([
                        24 => '24 horas',
                        72 => '3 dias',
                        168 => '7 dias',
                        720 => '30 dias',
                    ])
                    ->default(72)
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $resultado = app(ResultadoPeriodoDashboardShareService::class)->create(
                    $records,
                    $data['destinatario_nome'],
                    $data['destinatario_email'] ?? null,
                    (int) $data['validade_horas'],
                    Auth::id(),
                );
                $share = $resultado['share'];

                Notification::make()
                    ->success()
                    ->title('Link do dashboard gerado')
                    ->body('Link válido até '.$share->expires_at->format('d/m/Y H:i').': '.$resultado['url'])
                    ->actions([
                        NotificationAction::make('abrir_dashboard')
                            ->label('Abrir dashboard')
                            ->url($resultado['url'])
                            ->openUrlInNewTab(),
                    ])
                    ->persistent()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
