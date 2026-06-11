<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Tables;

use App\Filament\Actions\ExportPdfBulkAction;
use App\Models\Integrado;
use App\Models\ReceivedFiscalDocument;
use App\Services\MailInbound\LinkFiscalDocumentToIntegradoService;
use App\Services\MailInbound\ShipmentDocumentMatcher;
use App\Services\MailInbound\ShipmentTripService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ReceivedFiscalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['incomingEmail', 'integrado', 'saleGroups', 'remittanceGroups']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                TextColumn::make('tipo_documento')
                    ->label('Tipo de Documento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'sale' => 'Venda',
                        'remittance' => 'Remessa',
                        default => 'Desconhecido',
                    })
                    ->toggleable(),
                TextColumn::make('numero_nota')->label('Nº da Nota')->searchable()->toggleable(),
                TextColumn::make('emitente_documento')->label('Doc. do Emitente')->searchable()->toggleable(),
                TextColumn::make('destinatario_documento')->label('Doc. do Destinatário')->searchable()->toggleable(),
                TextColumn::make('integrado.nome')->label('Integrado')->placeholder('-')->wrap()->toggleable(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap()->toggleable(),
                TextColumn::make('incomingEmail.subject')->label('E-mail')->toggleable()->wrap(),
                TextColumn::make('emitido_em')->label('Emissão')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('id')
                    ->label('ID')
                    ->schema([
                        TextInput::make('id')
                            ->label('ID')
                            ->numeric(),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['id'] ?? null)) {
                            return null;
                        }

                        return "ID: {$data['id']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['id'] ?? null),
                            fn (Builder $query): Builder => $query->whereKey($data['id']),
                        );
                    }),
                SelectFilter::make('tipo_documento')
                    ->label('Tipo de documento')
                    ->options([
                        'sale' => 'Venda',
                        'remittance' => 'Remessa',
                        'unknown' => 'Desconhecido',
                    ])
                    ->multiple(),
                Filter::make('numero_nota')
                    ->label('Nº da nota')
                    ->schema([
                        TextInput::make('numero_nota')
                            ->label('Nº da Nota'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['numero_nota'] ?? null)) {
                            return null;
                        }

                        return "Nº da Nota: {$data['numero_nota']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['numero_nota'] ?? null),
                            fn (Builder $query): Builder => $query->where('numero_nota', 'like', "%{$data['numero_nota']}%"),
                        );
                    }),
                Filter::make('emitente_documento')
                    ->label('Doc. do emitente')
                    ->schema([
                        TextInput::make('emitente_documento')
                            ->label('Doc. do Emitente'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['emitente_documento'] ?? null)) {
                            return null;
                        }

                        return "Doc. Emitente: {$data['emitente_documento']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['emitente_documento'] ?? null),
                            fn (Builder $query): Builder => $query->where('emitente_documento', 'like', "%{$data['emitente_documento']}%"),
                        );
                    }),
                Filter::make('destinatario_documento')
                    ->label('Doc. do destinatário')
                    ->schema([
                        TextInput::make('destinatario_documento')
                            ->label('Doc. do Destinatário'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['destinatario_documento'] ?? null)) {
                            return null;
                        }

                        return "Doc. Destinatário: {$data['destinatario_documento']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['destinatario_documento'] ?? null),
                            fn (Builder $query): Builder => $query->where('destinatario_documento', 'like', "%{$data['destinatario_documento']}%"),
                        );
                    }),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('integrado', 'nome')
                    ->searchable()
                    ->multiple(),
                Filter::make('incoming_email_subject')
                    ->label('E-mail')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Assunto do E-mail'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['subject'] ?? null)) {
                            return null;
                        }

                        return "E-mail: {$data['subject']}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['subject'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas('incomingEmail', function (Builder $emailQuery) use ($data): void {
                                $emailQuery->where('subject', 'like', "%{$data['subject']}%");
                            }),
                        );
                    }),
                DateRangeFilter::make('emitido_em')
                    ->label('Emissão')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                TernaryFilter::make('possui_integrado')
                    ->label('Integrado vinculado?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com integrado')
                    ->falseLabel('Sem integrado')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('integrado_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('integrado_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('pendencia')
                    ->label('Pendência')
                    ->schema([
                        Select::make('pendencia')
                            ->label('Pendência')
                            ->options([
                                'fora_regra' => 'Fora da regra fiscal',
                                'venda_sem_referencia' => 'Venda sem chave referenciada',
                                'remessa_sem_venda' => 'Remessa sem número da venda',
                                'remessa_sem_integrado' => 'Remessa sem integrado',
                                'aguardando_par' => 'Aguardando documento par',
                                'pareado_concluido' => 'Pareado ou concluído',
                            ]),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['pendencia'] ?? null)) {
                            return null;
                        }

                        return match ($data['pendencia']) {
                            'fora_regra' => 'Pendência: Fora da regra fiscal',
                            'venda_sem_referencia' => 'Pendência: Venda sem chave referenciada',
                            'remessa_sem_venda' => 'Pendência: Remessa sem número da venda',
                            'remessa_sem_integrado' => 'Pendência: Remessa sem integrado',
                            'aguardando_par' => 'Pendência: Aguardando documento par',
                            'pareado_concluido' => 'Pendência: Pareado ou concluído',
                            default => null,
                        };
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['pendencia'] ?? null) {
                            'fora_regra' => $query->where('tipo_documento', 'unknown'),
                            'venda_sem_referencia' => $query
                                ->where('tipo_documento', 'sale')
                                ->whereNull('referenced_nfe_key'),
                            'remessa_sem_venda' => $query
                                ->where('tipo_documento', 'remittance')
                                ->whereNull('referenced_sale_number'),
                            'remessa_sem_integrado' => $query
                                ->where('tipo_documento', 'remittance')
                                ->whereNotNull('referenced_sale_number')
                                ->whereNull('integrado_id'),
                            'aguardando_par' => $query
                                ->where('tipo_documento', '!=', 'unknown')
                                ->where(function (Builder $pendingQuery): void {
                                    $pendingQuery
                                        ->where(function (Builder $saleQuery): void {
                                            $saleQuery
                                                ->where('tipo_documento', 'sale')
                                                ->whereNotNull('referenced_nfe_key');
                                        })
                                        ->orWhere(function (Builder $remittanceQuery): void {
                                            $remittanceQuery
                                                ->where('tipo_documento', 'remittance')
                                                ->whereNotNull('referenced_sale_number')
                                                ->whereNotNull('integrado_id');
                                        });
                                })
                                ->whereDoesntHave('saleGroups')
                                ->whereDoesntHave('remittanceGroups'),
                            'pareado_concluido' => $query
                                ->where(function (Builder $matchedQuery): void {
                                    $matchedQuery
                                        ->whereHas('saleGroups')
                                        ->orWhereHas('remittanceGroups');
                                }),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('vincular_integrado')
                    ->label('Vincular Integrado')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->iconButton()
                    ->visible(fn (ReceivedFiscalDocument $record): bool => $record->tipo_documento === 'remittance')
                    ->schema([
                        TextInput::make('destinatario_nome_xml')
                            ->label('Nome no XML')
                            ->default(fn (ReceivedFiscalDocument $record): ?string => $record->destinatario_nome)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('destinatario_documento_xml')
                            ->label('Documento no XML')
                            ->default(fn (ReceivedFiscalDocument $record): ?string => $record->destinatario_documento)
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('integrado_id')
                            ->label('Integrado equivalente')
                            ->options(fn () => Integrado::query()->orderBy('nome')->pluck('nome', 'id')->toArray())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (ReceivedFiscalDocument $record, array $data, LinkFiscalDocumentToIntegradoService $service): void {
                        $integrado = Integrado::query()->findOrFail($data['integrado_id']);

                        $service->handle($record, $integrado);

                        Notification::make()
                            ->success()
                            ->title('Integrado vinculado')
                            ->body("Documento fiscal {$record->id} vinculado ao integrado {$integrado->nome}.")
                            ->send();
                    }),
                Action::make('reprocessar_documento')
                    ->label('Reprocessar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (ReceivedFiscalDocument $record, ShipmentDocumentMatcher $matcher, ShipmentTripService $shipmentTripService): void {
                        $group = $matcher->match($record->fresh());

                        if ($group) {
                            $shipmentTripService->createFromGroup($group->id);
                        }

                        Notification::make()
                            ->success()
                            ->title('Documento reprocessado')
                            ->body("Documento fiscal {$record->id} reavaliado manualmente.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportPdfBulkAction::make(
                        'exportar_pdf',
                        'Documentos Fiscais Recebidos',
                        [
                            ['key' => 'id', 'label' => 'ID', 'align' => 'center', 'width' => '5%'],
                            ['key' => 'tipo_doc', 'label' => 'Tipo', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'numero_nota', 'label' => 'N° da Nota', 'align' => 'center', 'width' => '10%'],
                            ['key' => 'emitente', 'label' => 'Emitente', 'width' => '12%'],
                            ['key' => 'destinatario', 'label' => 'Destinatario', 'width' => '12%'],
                            ['key' => 'integrado', 'label' => 'Integrado', 'width' => '14%'],
                            ['key' => 'pendencia', 'label' => 'Situacao', 'width' => '18%'],
                            ['key' => 'email_subject', 'label' => 'Email', 'width' => '18%'],
                            ['key' => 'emitido_em', 'label' => 'Emissao', 'align' => 'center', 'width' => '10%'],
                        ],
                        fn ($records) => $records->load(['incomingEmail', 'integrado'])
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'tipo_doc' => match ($r->tipo_documento) {
                                    'sale' => 'Venda',
                                    'remittance' => 'Remessa',
                                    default => 'Desconhecido',
                                },
                                'numero_nota' => e($r->numero_nota ?? '-'),
                                'emitente' => e($r->emitente_documento ?? '-'),
                                'destinatario' => e($r->destinatario_documento ?? '-'),
                                'integrado' => e($r->integrado?->nome ?? '-'),
                                'pendencia' => e($r->pending_summary ?? '-'),
                                'email_subject' => e(Str::limit($r->incomingEmail?->subject, 50) ?? '-'),
                                'emitido_em' => $r->emitido_em?->format('d/m/Y H:i') ?? '-',
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
