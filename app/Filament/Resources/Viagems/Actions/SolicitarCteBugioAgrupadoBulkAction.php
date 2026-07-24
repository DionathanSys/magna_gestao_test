<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\NotificacaoService as notify;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class SolicitarCteBugioAgrupadoBulkAction
{
    protected static ?Collection $motoristasCache = null;

    public static function make(): BulkAction
    {
        $syncIntegradoData = function (?string $state, Set $set): void {
            if (! $state) {
                $set('integrado_municipio_uf', '');

                return;
            }

            $integrado = Integrado::find($state);

            $set('integrado_municipio_uf', $integrado ? ($integrado->municipio ?? '').' - '.($integrado->estado ?? '') : '');
        };

        return BulkAction::make('solicitar_cte_bugio_agrupado')
            ->label('Solicitar CTe Agrupado')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalWidth(Width::FiveExtraLarge)
            ->fillForm(fn (Collection $records): array => self::getFormData($records))
            ->schema([
                Section::make('Resumo das Viagens')
                    ->columns(2)
                    ->schema([
                        TextInput::make('resumo_viagens')
                            ->label('Viagens')
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('resumo_notas')
                            ->label('Notas Fiscais')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('resumo_anexos')
                            ->label('Anexos')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('documento_transporte_preview')
                            ->label('Doc. Transp. do Grupo')
                            ->readOnly()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Solicitação')
                    ->columns(6)
                    ->schema([
                        Select::make('integrado_id')
                            ->label('Integrado que constará no email')
                            ->options(fn (): array => Integrado::query()->orderBy('nome')->pluck('nome', 'id')->toArray())
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
                            ->options(fn () => self::getMotoristasOptions())
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
                            ->label('KM Total das Viagens')
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
            ->action(function (Collection $records, array $data, SolicitarCteBugioFromViagem $service): void {
                try {
                    self::validateSelection($records);
                    $service->handleAgrupado($records, $data);

                    Notification::make()
                        ->success()
                        ->title($data['tipo_documento'] === TipoDocumentoEnum::NFS->value ? 'Documento de Frete criado' : 'Solicitação de CTe agrupada enviada')
                        ->body($data['tipo_documento'] === TipoDocumentoEnum::NFS->value
                            ? 'O Documento de Frete agrupado foi criado com base na NFS das viagens.'
                            : 'O envio do email de solicitação de CTe agrupado foi disparado com os anexos das viagens.')
                        ->send();
                } catch (\Throwable $exception) {
                    notify::error('Erro ao processar solicitação agrupada de CTe', $exception->getMessage());
                }
            })
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion();
    }

    protected static function getFormData(Collection $records): array
    {
        self::validateSelection($records);

        $records->loadMissing('veiculo', 'cargas.integrado', 'attachments.receivedFiscalDocument', 'attachments.incomingEmailAttachment');

        $primeiraViagem = $records->first();
        $motoristas = self::getMotoristas();
        $motoristaPadraoCpf = data_get($primeiraViagem->veiculo?->informacoes_complementares, 'motorista_padrao_cte_cpf');

        if ($motoristaPadraoCpf && ! $motoristas->contains(fn (array $motorista): bool => (string) ($motorista['cpf'] ?? '') === (string) $motoristaPadraoCpf)) {
            $motoristaPadraoCpf = null;
        }

        $integrado = $records
            ->flatMap(fn (Viagem $viagem) => $viagem->cargas)
            ->map(fn ($carga) => $carga->integrado)
            ->filter()
            ->first();

        $kmRota = $records
            ->flatMap(fn (Viagem $viagem) => $viagem->cargas)
            ->map(fn ($carga) => $carga->integrado)
            ->filter()
            ->sum(fn (Integrado $integrado): float => (float) ($integrado->km_rota ?? 0));

        if ($kmRota <= 0) {
            $kmRota = $records->sum(fn (Viagem $viagem): float => (float) ($viagem->km_pago ?? 0));
        }

        $fiscalDocuments = $records
            ->flatMap(fn (Viagem $viagem) => $viagem->attachments)
            ->map(fn ($attachment) => $attachment->receivedFiscalDocument)
            ->filter()
            ->unique('id')
            ->values();

        $pesoCarga = (float) $fiscalDocuments->sum(fn ($document) => (float) ($document?->peso_carga ?? 0));
        $resumoNotas = $fiscalDocuments
            ->pluck('numero_nota')
            ->filter()
            ->unique()
            ->implode(', ') ?: 'Não informado';
        $resumoAnexos = $records
            ->flatMap(fn (Viagem $viagem) => $viagem->attachments)
            ->map(fn ($attachment) => $attachment->incomingEmailAttachment?->original_filename)
            ->filter()
            ->unique()
            ->implode(', ') ?: 'Não informado';
        $documentosTransporte = $records
            ->map(fn (Viagem $viagem): ?string => self::documentoTransporteReal($viagem))
            ->filter()
            ->unique()
            ->values();

        return [
            'resumo_viagens' => $records->pluck('numero_viagem')->implode(', ').' | Placa '.($primeiraViagem->veiculo?->placa ?? 'N/A'),
            'resumo_notas' => $resumoNotas,
            'resumo_anexos' => $resumoAnexos,
            'documento_transporte_preview' => $documentosTransporte->first() ?? 'Será gerado ao confirmar',
            'integrado_id' => $integrado?->id,
            'integrado_municipio_uf' => $integrado ? ($integrado->municipio ?? '').' - '.($integrado->estado ?? '') : '',
            'motorista' => $motoristaPadraoCpf,
            'data_competencia' => $primeiraViagem->data_competencia,
            'tipo_documento' => TipoDocumentoEnum::CTE->value,
            'cte_retroativo' => true,
            'km_rota' => $kmRota,
            'valor_frete_preview' => number_format($kmRota * (float) db_config('config-bugio.valor-quilometro', 0), 2, '.', ''),
            'peso_carga_preview' => $pesoCarga > 0 ? number_format($pesoCarga, 3, ',', '.') : 'Não informado',
            'peso_carga' => $pesoCarga > 0 ? $pesoCarga : null,
        ];
    }

    protected static function validateSelection(Collection $records): void
    {
        if ($records->count() < 2) {
            throw new \InvalidArgumentException('Selecione pelo menos duas viagens para agrupar a solicitação.');
        }

        $records->loadMissing('cargas.integrado');

        if ($records->pluck('veiculo_id')->filter()->unique()->count() !== 1) {
            throw new \InvalidArgumentException('Selecione somente viagens do mesmo veículo.');
        }

        $documentosTransporte = $records
            ->map(fn (Viagem $viagem): ?string => self::documentoTransporteReal($viagem))
            ->filter()
            ->unique()
            ->values();

        if ($documentosTransporte->count() > 1) {
            throw new \InvalidArgumentException('As viagens selecionadas possuem documentos de transporte diferentes.');
        }

        $semIntegrado = $records->filter(fn (Viagem $viagem): bool => $viagem->cargas->pluck('integrado')->filter()->isEmpty());

        if ($semIntegrado->isNotEmpty()) {
            throw new \InvalidArgumentException('Todas as viagens selecionadas precisam ter integrado vinculado.');
        }

        $semAnexos = $records->filter(fn (Viagem $viagem): bool => ! $viagem->attachments()->exists());

        if ($semAnexos->isNotEmpty()) {
            throw new \InvalidArgumentException('Todas as viagens selecionadas precisam ter anexos.');
        }
    }

    protected static function getMotoristas(): Collection
    {
        if (self::$motoristasCache instanceof Collection) {
            return self::$motoristasCache;
        }

        return self::$motoristasCache = collect(db_config('config-bugio.motoristas'));
    }

    protected static function getMotoristasOptions(): array
    {
        return self::getMotoristas()
            ->pluck('motorista', 'cpf')
            ->toArray();
    }

    protected static function documentoTransporteReal(Viagem $viagem): ?string
    {
        $documentoTransporte = trim((string) $viagem->documento_transporte);

        if ($documentoTransporte === '') {
            return null;
        }

        return $documentoTransporte !== trim((string) $viagem->numero_viagem)
            ? $documentoTransporte
            : null;
    }
}
