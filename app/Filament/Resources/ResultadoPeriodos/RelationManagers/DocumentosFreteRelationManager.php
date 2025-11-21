<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\Frete\TipoDocumentoEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosFreteRelationManager extends RelationManager
{
    protected static string $relationship = 'documentosFrete';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'id')
                    ->required(),
                TextInput::make('parceiro_origem'),
                TextInput::make('parceiro_destino'),
                TextInput::make('numero_documento'),
                TextInput::make('documento_transporte'),
                Select::make('tipo_documento')
                    ->options(TipoDocumentoEnum::class),
                DatePicker::make('data_emissao')
                    ->required(),
                TextInput::make('valor_total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_icms')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('valor_liquido')
                    ->numeric(),
                TextInput::make('municipio'),
                TextInput::make('estado'),
                Select::make('viagem_id')
                    ->relationship('viagem', 'id'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('resultado_periodo_id')
            ->columns([
                TextColumn::make('veiculo.id')
                    ->searchable(),
                TextColumn::make('parceiro_origem')
                    ->searchable(),
                TextColumn::make('parceiro_destino')
                    ->searchable(),
                TextColumn::make('numero_documento')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->searchable(),
                TextColumn::make('tipo_documento')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_emissao')
                    ->date()
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_icms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_liquido')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('municipio')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('viagem.id')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
