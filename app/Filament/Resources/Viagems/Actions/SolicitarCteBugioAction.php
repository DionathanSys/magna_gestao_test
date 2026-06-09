<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\Viagem;
use App\Services\NotificacaoService as notify;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
            ->tooltip('Solicitar CTe ou registrar NFS com anexos da viagem')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->iconButton()
            ->visible(fn (Viagem $record): bool => $record->attachments()->exists())
            ->schema([
                Select::make('veiculo_id')
                    ->label('Veículo')
                    ->options(fn () => Veiculo::query()->where('is_active', true)->orderBy('placa')->pluck('placa', 'id')->toArray())
                    ->default(fn (Viagem $record) => $record->veiculo_id)
                    ->searchable()
                    ->required(),
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
                Select::make('tipo_documento')
                    ->label('Tipo de Documento')
                    ->options(TipoDocumentoEnum::toSelectArray())
                    ->default(TipoDocumentoEnum::CTE->value)
                    ->live()
                    ->required(),
                Select::make('motorista')
                    ->label('Motorista')
                    ->options(fn () => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                    ->searchable()
                    ->required(),
                TextInput::make('km_rota')
                    ->label('KM da Viagem')
                    ->numeric()
                    ->default(function (Viagem $record) {
                        $record->loadMissing('cargas.integrado');
                        return (float) ($record->cargas->first()?->integrado?->km_rota ?? $record->km_pago ?? 0);
                    })
                    ->required(),
                Placeholder::make('valor_frete_preview')
                    ->label('Valor do Frete')
                    ->content(fn (Get $get): string => 'R$ ' . number_format(((float) ($get('km_rota') ?? 0)) * (float) db_config('config-bugio.valor-quilometro', 0), 2, ',', '.')),
                Placeholder::make('peso_carga_preview')
                    ->label('Peso da Carga')
                    ->content(function (Viagem $record): string {
                        $record->loadMissing('attachments.receivedFiscalDocument');
                        $peso = $record->attachments
                            ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->peso_carga)
                            ->filter()
                            ->first();

                        return $peso ? number_format((float) $peso, 3, ',', '.') . ' kg' : 'Não informado';
                    }),
                TextInput::make('peso_carga')
                    ->default(function (Viagem $record): ?float {
                        $record->loadMissing('attachments.receivedFiscalDocument');
                        return $record->attachments
                            ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->peso_carga)
                            ->filter()
                            ->first();
                    })
                    ->hidden(),
                DatePicker::make('data_competencia')
                    ->label('Data Competência')
                    ->default(fn (Viagem $record) => $record->data_competencia)
                    ->required(),
                Toggle::make('cte_retroativo')
                    ->label('CTe Retroativo')
                    ->default(true)
                    ->inline(false)
                    ->visible(fn (Get $get): bool => in_array($get('tipo_documento'), [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value], true)),
                TextInput::make('cte_referencia')
                    ->label('CTe de Referência')
                    ->required(fn (Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                    ->visible(fn (Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value),
            ])
            ->action(function (Viagem $record, array $data, SolicitarCteBugioFromViagem $service): void {
                try {
                    $service->handle($record, $data);

                    Notification::make()
                        ->success()
                        ->title($data['tipo_documento'] === TipoDocumentoEnum::NFS->value ? 'Documento de Frete criado' : 'Solicitação de CTe enviada')
                        ->body($data['tipo_documento'] === TipoDocumentoEnum::NFS->value
                            ? 'O Documento de Frete foi criado com base na NFS da viagem.'
                            : 'O envio do email de solicitação de CTe foi disparado com os anexos da viagem.')
                        ->send();
                } catch (\Throwable $exception) {
                    notify::error('Erro ao processar ação da viagem', $exception->getMessage());
                }
            })
            ->requiresConfirmation();
    }
}
