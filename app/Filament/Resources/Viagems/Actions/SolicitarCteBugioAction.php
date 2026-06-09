<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\NotificacaoService as notify;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class SolicitarCteBugioAction
{
    public static function make(): Action
    {
        return Action::make('solicitar_cte_bugio')
            ->label('Solicitar CTe')
            ->tooltip('Solicitar CTe com anexos da viagem')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->iconButton()
            ->visible(fn (Viagem $record): bool => $record->attachments()->exists() && $record->cargas()->whereNotNull('integrado_id')->exists())
            ->schema([
                Select::make('integrado_id')
                    ->label('Integrado')
                    ->options(function (Viagem $record): array {
                        $record->loadMissing('cargas.integrado');

                        return $record->cargas
                            ->map(fn ($carga) => $carga->integrado)
                            ->filter()
                            ->unique('id')
                            ->mapWithKeys(fn (Integrado $integrado) => [$integrado->id => $integrado->nome])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),
                Select::make('motorista')
                    ->label('Motorista')
                    ->options(fn () => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                    ->searchable()
                    ->required(),
                Toggle::make('cte_retroativo')
                    ->label('CTe Retroativo')
                    ->default(true)
                    ->inline(false),
                Toggle::make('cte_complementar')
                    ->label('CTe Complementar')
                    ->default(false)
                    ->inline(false),
                TextInput::make('cte_referencia')
                    ->label('CTe de Referência')
                    ->required(fn (Get $get): bool => (bool) $get('cte_complementar')),
            ])
            ->action(function (Viagem $record, array $data, SolicitarCteBugioFromViagem $service): void {
                try {
                    $service->handle($record, $data);

                    Notification::make()
                        ->success()
                        ->title('Solicitação de CTe criada')
                        ->body('A solicitação Bugio foi criada a partir da viagem e o envio foi disparado.')
                        ->send();
                } catch (\Throwable $exception) {
                    notify::error('Erro ao solicitar CTe', $exception->getMessage());
                }
            })
            ->requiresConfirmation();
    }
}
