<?php

namespace App\Filament\Resources\CteEmailRequests\Tables;

use App\Filament\Actions\ExportPdfBulkAction;
use App\Models\CteEmailRequest;
use App\Models\Veiculo;
use App\Services\Bugio\CteReturnEmailProcessingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CteEmailRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['viagem.veiculo', 'integrado']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                TextColumn::make('documento_transporte')->label('Doc. Transporte')->searchable()->toggleable(),
                TextColumn::make('viagem.numero_viagem')->label('Viagem')->searchable()->placeholder('-')->toggleable(),
                TextColumn::make('viagem.veiculo.placa')->label('Placa')->placeholder('-')->toggleable(),
                TextColumn::make('integrado.nome')->label('Integrado')->wrap()->placeholder('-')->toggleable(),
                TextColumn::make('tipo_documento_solicitado')->label('Tipo')->toggleable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_send' => 'warning',
                        'sent' => 'info',
                        'response_received' => 'primary',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('sent_subject')->label('Assunto enviado')->wrap()->limit(60)->toggleable(),
                TextColumn::make('requested_at')->label('Solicitado em')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
                TextColumn::make('sent_at')->label('Enviado em')->dateTime('d/m/Y H:i')->sortable()->placeholder('-')->toggleable(),
                TextColumn::make('last_response_at')->label('Resposta em')->dateTime('d/m/Y H:i')->sortable()->placeholder('-')->toggleable(),
                TextColumn::make('completed_at')->label('Concluido em')->dateTime('d/m/Y H:i')->sortable()->placeholder('-')->toggleable(),
                TextColumn::make('error_message')->label('Erro')->wrap()->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('tipo_documento_solicitado')
                    ->label('Tipo')
                    ->options(fn () => CteEmailRequest::query()
                        ->whereNotNull('tipo_documento_solicitado')
                        ->distinct()
                        ->orderBy('tipo_documento_solicitado')
                        ->pluck('tipo_documento_solicitado', 'tipo_documento_solicitado')
                        ->toArray()),
                SelectFilter::make('placa')
                    ->label('Placa')
                    ->options(fn () => Veiculo::query()
                        ->orderBy('placa')
                        ->pluck('placa', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $veiculoId): Builder => $query->whereHas('viagem', fn (Builder $query): Builder => $query->where('veiculo_id', $veiculoId))
                    )),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('integrado', 'nome')
                    ->searchable()
                    ->preload(),
                Filter::make('requested_at')
                    ->label('Solicitado em')
                    ->schema([
                        DatePicker::make('requested_from')->label('De'),
                        DatePicker::make('requested_until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['requested_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('requested_at', '>=', $date))
                            ->when($data['requested_until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('requested_at', '<=', $date));
                    }),
            ])
            ->groups([
                Group::make('documento_transporte')
                    ->label('Documento Transporte')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
                Group::make('viagem.veiculo.placa')
                    ->label('Placa')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
                Group::make('status')
                    ->label('Status')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->recordActions([
                Action::make('reprocessar_request')
                    ->label('Reprocessar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (CteEmailRequest $record, CteReturnEmailProcessingService $service): void {
                        $service->reprocessRequest($record->id);

                        Notification::make()
                            ->success()
                            ->title('Reprocessamento disparado')
                            ->body("Request {$record->id} enviado para reprocessamento dos anexos.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportPdfBulkAction::make(
                        'exportar_pdf',
                        'Solicitacoes CTe',
                        [
                            ['key' => 'id', 'label' => 'ID', 'align' => 'center', 'width' => '5%'],
                            ['key' => 'doc_transporte', 'label' => 'Doc. Transporte', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'viagem', 'label' => 'Viagem', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'placa', 'label' => 'Placa', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'integrado', 'label' => 'Integrado', 'width' => '15%'],
                            ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'status', 'label' => 'Status', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'assunto', 'label' => 'Assunto', 'width' => '20%'],
                            ['key' => 'solicitado_em', 'label' => 'Solicitado', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'concluido_em', 'label' => 'Concluido', 'align' => 'center', 'width' => '10%'],
                        ],
                        fn ($records) => $records->load(['viagem.veiculo', 'integrado'])
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'doc_transporte' => e($r->documento_transporte ?? '-'),
                                'viagem' => e($r->viagem?->numero_viagem ?? '-'),
                                'placa' => e($r->viagem?->veiculo?->placa ?? '-'),
                                'integrado' => e($r->integrado?->nome ?? '-'),
                                'tipo' => e($r->tipo_documento_solicitado ?? '-'),
                                'status' => e($r->status ?? '-'),
                                'assunto' => e(Str::limit($r->sent_subject, 50) ?? '-'),
                                'solicitado_em' => $r->requested_at?->format('d/m/Y H:i') ?? '-',
                                'concluido_em' => $r->completed_at?->format('d/m/Y H:i') ?? '-',
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
