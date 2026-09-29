<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Filament\Resources\PlanoPreventivos\PlanoPreventivoResource;
use App\Models\PlanoPreventivo;
use App\Services\Servico\ServicoCacheService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanoPreventivoRelationManager extends RelationManager
{
    protected static string $relationship = 'planoPreventivo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->required(),
                TextInput::make('periodicidade'),
                TextInput::make('intervalo')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('itens'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('descricao'),
                TextEntry::make('periodicidade'),
                TextEntry::make('intervalo')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (PlanoPreventivo $record): string => PlanoPreventivoResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periodicidade')
                    ->label('Periodicidade')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('intervalo')
                    ->label('Intervalo (km)')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo?')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('itens')
                    ->label('Itens')
                    ->getStateUsing(fn (PlanoPreventivo $record): array => collect($record->itens ?? [])
                        ->map(fn ($item): ?string => ServicoCacheService::getServicoLabel(data_get($item, 'servico_id')))
                        ->filter()
                        ->values()
                        ->all())
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->placeholder('Nenhum item')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ]);
    }
}
