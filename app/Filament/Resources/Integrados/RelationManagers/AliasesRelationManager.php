<?php

namespace App\Filament\Resources\Integrados\RelationManagers;

use App\Services\Integrado\IntegradoDestinoService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AliasesRelationManager extends RelationManager
{
    protected static string $relationship = 'aliases';

    protected static ?string $title = 'Nomes recebidos na integração';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alias')
                ->label('Nome recebido')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alias')
            ->columns([
                TextColumn::make('alias')->label('Nome recebido')->searchable(),
                TextColumn::make('alias_normalizado')->label('Nome normalizado')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'alias_normalizado' => app(IntegradoDestinoService::class)->normalizar($data['alias']),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'alias_normalizado' => app(IntegradoDestinoService::class)->normalizar($data['alias']),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
