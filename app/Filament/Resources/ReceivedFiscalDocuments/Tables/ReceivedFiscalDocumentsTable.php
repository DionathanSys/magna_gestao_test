<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceivedFiscalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['incomingEmail', 'integrado', 'saleGroups', 'remittanceGroups']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('tipo_documento')->label('Tipo')->badge(),
                TextColumn::make('numero_nota')->label('Nota')->searchable(),
                TextColumn::make('emitente_documento')->label('Doc. Emissor')->searchable(),
                TextColumn::make('destinatario_documento')->label('Doc. Destino')->searchable(),
                TextColumn::make('integrado.nome')->label('Integrado')->placeholder('-')->wrap(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap(),
                TextColumn::make('incomingEmail.subject')->label('Email')->toggleable()->wrap(),
                TextColumn::make('emitido_em')->label('Emissao')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_documento')
                    ->options([
                        'sale' => 'Sale',
                        'remittance' => 'Remittance',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
            ]);
    }
}
