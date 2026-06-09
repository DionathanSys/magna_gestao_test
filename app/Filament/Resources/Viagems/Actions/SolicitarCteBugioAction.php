<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\NotificacaoService as notify;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
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
            ->visible(fn(Viagem $record): bool => $record->attachments()->exists())
            ->modalWidth(Width::FiveExtraLarge)
            ->schema([
                Section::make('Resumo da Viagem')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('resumo_viagem')
                            ->label('Viagem')
                            ->content(fn(Viagem $record): string => $record->numero_viagem . ' | Placa ' . ($record->veiculo?->placa ?? 'N/A')),
                        Placeholder::make('resumo_notas')
                            ->label('Notas Fiscais')
                            ->content(function (Viagem $record): string {
                                $record->loadMissing('attachments.receivedFiscalDocument');

                                return $record->attachments
                                    ->map(fn($attachment) => $attachment->receivedFiscalDocument?->numero_nota)
                                    ->filter()
                                    ->unique()
                                    ->implode(', ') ?: 'Não informado';
                            }),
                        TextEntry::make('resumo_anexos')
                            ->label('Anexos')
                            ->state(function (Viagem $record): string {
                                $record->loadMissing('attachments.incomingEmailAttachment');

                                return $record->attachments
                                    ->map(fn($attachment) => $attachment->incomingEmailAttachment?->original_filename)
                                    ->filter()
                                    ->unique()
                                    ->implode(', ') ?: 'Não informado';
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make('Solicitação')
                    ->columns(6)
                    ->schema([
                        Select::make('integrado_id')
                            ->label('Integrado')
                            ->options(function (Viagem $record): array {
                                $record->loadMissing('cargas.integrado');

                                return $record->cargas
                                    ->map(fn($carga) => $carga->integrado)
                                    ->filter()
                                    ->unique('id')
                                    ->mapWithKeys(fn(Integrado $integrado) => [$integrado->id => $integrado->nome])
                                    ->toArray();
                            })
                            ->default(function (Viagem $record): ?int {
                                $record->loadMissing('cargas.integrado');

                                return $record->cargas
                                    ->pluck('integrado_id')
                                    ->filter()
                                    ->first();
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(3),
                        Select::make('motorista')
                            ->label('Motorista')
                            ->options(fn() => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                            ->searchable()
                            ->required()
                            ->columnSpan(3),
                        Select::make('tipo_documento')
                            ->label('Tipo de Documento')
                            ->options(TipoDocumentoEnum::toSelectArray())
                            ->default(TipoDocumentoEnum::CTE->value)
                            ->live()
                            ->required()
                            ->columnSpan(2),
                        DatePicker::make('data_competencia')
                            ->label('Data Competência')
                            ->default(fn(Viagem $record) => $record->data_competencia)
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('km_rota')
                            ->label('KM da Viagem')
                            ->numeric()
                            ->default(function (Viagem $record) {
                                $record->loadMissing('cargas.integrado');
                                return (float) ($record->cargas->first()?->integrado?->km_rota ?? $record->km_pago ?? 0);
                            })
                            ->required()
                            ->columnSpan(2),
                        TextEntry::make('valor_frete_preview')
                            ->label('Valor do Frete')
                            ->state(fn(Get $get): string => 'R$ ' . number_format(((float) ($get('km_rota') ?? 0)) * (float) db_config('config-bugio.valor-quilometro', 0), 2, ',', '.'))
                            ->columnSpan(2)
                            ->columnStart(1),
                        TextEntry::make('peso_carga_preview')
                            ->label('Peso da Carga')
                            ->state(function (Viagem $record): string {
                                $record->loadMissing('attachments.receivedFiscalDocument');
                                $peso = $record->attachments
                                    ->map(fn($attachment) => $attachment->receivedFiscalDocument?->peso_carga)
                                    ->filter()
                                    ->first();

                                return $peso ? number_format((float) $peso, 3, ',', '.') . ' kg' : 'Não informado';
                            })
                            ->columnSpan(2),
                        TextInput::make('peso_carga')
                            ->default(function (Viagem $record): ?float {
                                $record->loadMissing('attachments.receivedFiscalDocument');
                                return $record->attachments
                                    ->map(fn($attachment) => $attachment->receivedFiscalDocument?->peso_carga)
                                    ->filter()
                                    ->first();
                            })
                            ->hidden(),
                        Toggle::make('cte_retroativo')
                            ->label('CTe Retroativo')
                            ->default(true)
                            ->inline(false)
                            ->visible(fn(Get $get): bool => in_array($get('tipo_documento'), [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value], true))
                            ->columnSpan(2),
                        TextInput::make('cte_referencia')
                            ->label('CTe de Referência')
                            ->required(fn(Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                            ->visible(fn(Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                            ->columnSpan(4),
                    ]),
            ])
            ->modalDescription(fn(Viagem $record): string => 'Viagem ' . $record->numero_viagem . ' | Placa ' . ($record->veiculo?->placa ?? 'N/A'))
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
