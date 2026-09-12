<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Mail\ResultadoPeriodoDashboardShareMail;
use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardService;
use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardShareService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CompartilharDashboardAction
{
    public static function make(): Action
    {
        return Action::make('compartilhar_dashboard')
            ->label('Compartilhar dashboard')
            ->icon(Heroicon::Share)
            ->requiresConfirmation()
            ->modalHeading('Enviar dashboard por e-mail')
            ->modalDescription('Serão incluídos todos os resultados cuja data inicial esteja dentro do período informado.')
            ->schema([
                DatePicker::make('data_inicio')
                    ->label('Data inicial')
                    ->default(now()->startOfMonth()->toDateString())
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data final')
                    ->default(now()->endOfMonth()->toDateString())
                    ->afterOrEqual('data_inicio')
                    ->required(),
                TextInput::make('destinatario_nome')
                    ->label('Nome do destinatário')
                    ->required()
                    ->maxLength(120),
                TextInput::make('destinatario_email')
                    ->label('E-mail do destinatário')
                    ->email()
                    ->required()
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
            ->action(function (array $data): void {
                $dashboardService = app(ResultadoPeriodoDashboardService::class);
                $records = $dashboardService->recordsForPeriod($data['data_inicio'], $data['data_fim']);

                if ($records->isEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Nenhum resultado encontrado')
                        ->body('Não há resultados com data inicial dentro do período informado.')
                        ->send();

                    return;
                }

                $shareResult = app(ResultadoPeriodoDashboardShareService::class)->create(
                    $data['data_inicio'],
                    $data['data_fim'],
                    $data['destinatario_nome'],
                    $data['destinatario_email'],
                    (int) $data['validade_horas'],
                    Auth::id(),
                );
                $share = $shareResult['share'];
                $periodo = $share->data_inicio->format('d/m/Y').' a '.$share->data_fim->format('d/m/Y');

                try {
                    Mail::to($share->destinatario_email)->send(new ResultadoPeriodoDashboardShareMail(
                        destinatarioNome: $share->destinatario_nome,
                        url: $shareResult['url'],
                        periodo: $periodo,
                        expiraEm: $share->expires_at->format('d/m/Y H:i'),
                        quantidadeResultados: $records->count(),
                    ));
                } catch (Throwable $exception) {
                    $share->delete();
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('Não foi possível enviar o e-mail')
                        ->body('O compartilhamento foi cancelado. Verifique a configuração do serviço de e-mail.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Dashboard enviado por e-mail')
                    ->body('O link do período '.$periodo.' foi enviado para '.$share->destinatario_email.'. Ele expira em '.$share->expires_at->format('d/m/Y H:i').'.')
                    ->send();
            });
    }
}
