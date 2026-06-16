<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\NotificacaoService as notify;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;

class SolicitarCteBugioAction
{
    public static function make(): Action
    {
        $syncIntegradoData = function (?string $state, Set $set): void {
            if (! $state) {
                $set('integrado_municipio_uf', '');
                $set('km_rota', 0);
                $set('valor_frete_preview', number_format(0, 2, '.', ''));

                return;
            }

            $integrado = Integrado::find($state);
            $kmRota = (float) ($integrado?->km_rota ?? 0);
            $valorFrete = $kmRota * (float) db_config('config-bugio.valor-quilometro', 0);

            $set('integrado_municipio_uf', $integrado ? ($integrado->municipio ?? '').' - '.($integrado->estado ?? '') : '');
            $set('km_rota', $kmRota);
            $set('valor_frete_preview', number_format($valorFrete, 2, '.', ''));
        };

        return Action::make('solicitar_cte_bugio')
            ->label('Solicitar CTe')
            ->tooltip('Solicitar Document Frete')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->visible(fn (Viagem $record): bool => $record->attachments()->exists())
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm(function (Viagem $record): array {
                $record->loadMissing('veiculo', 'cargas.integrado', 'attachments.receivedFiscalDocument', 'attachments.incomingEmailAttachment');

                $motoristas = collect(db_config('config-bugio.motoristas'));
                $motoristaPadraoCpf = data_get($record->veiculo?->informacoes_complementares, 'motorista_padrao_cte_cpf');

                if ($motoristaPadraoCpf && ! $motoristas->contains(fn (array $motorista): bool => (string) ($motorista['cpf'] ?? '') === (string) $motoristaPadraoCpf)) {
                    $motoristaPadraoCpf = null;
                }

                $integrado = $record->cargas
                    ->map(fn ($carga) => $carga->integrado)
                    ->filter()
                    ->first();

                $kmRota = (float) ($integrado?->km_rota ?? $record->km_pago ?? 0);
                $pesoCarga = $record->attachments
                    ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->peso_carga)
                    ->filter()
                    ->first();
                $resumoNotas = $record->attachments
                    ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->numero_nota)
                    ->filter()
                    ->unique()
                    ->implode(', ') ?: 'Não informado';
                $resumoAnexos = $record->attachments
                    ->map(fn ($attachment) => $attachment->incomingEmailAttachment?->original_filename)
                    ->filter()
                    ->unique()
                    ->implode(', ') ?: 'Não informado';

                return [
                    'resumo_viagem' => $record->numero_viagem.' | Placa '.($record->veiculo?->placa ?? 'N/A'),
                    'resumo_notas' => $resumoNotas,
                    'resumo_anexos' => $resumoAnexos,
                    'integrado_id' => $integrado?->id,
                    'integrado_municipio_uf' => $integrado ? ($integrado->municipio ?? '').' - '.($integrado->estado ?? '') : '',
                    'motorista' => $motoristaPadraoCpf,
                    'data_competencia' => $record->data_competencia,
                    'tipo_documento' => TipoDocumentoEnum::CTE->value,
                    'cte_retroativo' => true,
                    'km_rota' => $kmRota,
                    'valor_frete_preview' => number_format($kmRota * (float) db_config('config-bugio.valor-quilometro', 0), 2, '.', ''),
                    'peso_carga_preview' => $pesoCarga ? number_format((float) $pesoCarga, 3, ',', '.') : 'Não informado',
                    'peso_carga' => $pesoCarga,
                ];
            })
            ->schema([
                Section::make('Resumo da Viagem')
                    ->columns(2)
                    ->schema([
                        TextInput::make('resumo_viagem')
                            ->label('Viagem')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('resumo_notas')
                            ->label('Notas Fiscais')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('resumo_anexos')
                            ->label('Anexos')
                            ->readOnly()
                            ->dehydrated(false)
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
                                    ->map(fn ($carga) => $carga->integrado)
                                    ->filter()
                                    ->unique('id')
                                    ->mapWithKeys(fn (Integrado $integrado) => [$integrado->id => $integrado->nome])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateHydrated($syncIntegradoData)
                            ->afterStateUpdated($syncIntegradoData)
                            ->columnSpan(3),
                        TextInput::make('integrado_municipio_uf')
                            ->label('Município - UF')
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan(3),
                        Select::make('motorista')
                            ->label('Motorista')
                            ->options(fn () => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                            ->autofocus()
                            ->searchable()
                            ->required()
                            ->columnSpan(3),
                        Select::make('tipo_documento')
                            ->label('Tipo de Documento')
                            ->options(TipoDocumentoEnum::toSelectArray())
                            ->live()
                            ->native(false)
                            ->required()
                            ->columnSpan(2),
                        DatePicker::make('data_competencia')
                            ->label('Data Competência')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('km_rota')
                            ->label('KM da Viagem')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $valorFrete = ((float) ($state ?? 0)) * (float) db_config('config-bugio.valor-quilometro', 0);

                                $set('valor_frete_preview', number_format($valorFrete, 2, '.', ''));
                            })
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('valor_frete_preview')
                            ->label('Valor do Frete')
                            ->readOnly()
                            ->dehydrated(false)
                            ->suffix('R$')
                            ->columnSpan(2)
                            ->columnStart(1),
                        TextInput::make('peso_carga_preview')
                            ->label('Peso da Carga')
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpan(2)
                            ->suffix('kg'),
                        TextInput::make('peso_carga')
                            ->hidden(),
                        Toggle::make('cte_retroativo')
                            ->label('CTe Retroativo')
                            ->inline(false)
                            ->visible(fn (Get $get): bool => in_array($get('tipo_documento'), [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value], true))
                            ->columnSpan(2),
                        TextInput::make('cte_referencia')
                            ->label('CTe de Referência')
                            ->required(fn (Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                            ->visible(fn (Get $get): bool => $get('tipo_documento') === TipoDocumentoEnum::CTE_COMPLEMENTO->value)
                            ->columnSpan(4),
                    ]),
            ])
            ->modalDescription(fn (Viagem $record): string => 'Viagem '.$record->numero_viagem.' | Placa '.($record->veiculo?->placa ?? 'N/A'))
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
