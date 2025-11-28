<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models;
use Carbon\Carbon;
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
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class DocumentosFreteRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

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
                TextColumn::make('parceiro_origem')
                    ->label('Parceiro Origem')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('parceiro_destino')
                    ->label('Parceiro Destino')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('numero_documento')
                    ->label('Nº Documento')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Documento Transporte')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->width('1%')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->money('BRL')
                    ->width('1%')
                    ->summarize(
                        Sum::make()
                            ->money('BRL', 100)
                            ->label('TT Valor Total')
                    )
                    ->sortable(),
                TextColumn::make('valor_icms')
                    ->money('BRL')
                    ->width('1%')
                    ->summarize(
                        Sum::make()
                            ->money('BRL', 100)
                            ->label('TT Vlr. ICMS')
                    )
                    ->sortable(),
                TextColumn::make('valor_liquido')
                    ->money('BRL')
                    ->width('1%')
                    ->summarize(
                        Sum::make()
                            ->money('BRL', 100)
                            ->label('TT Vlr. Líquido')
                    )
                    ->sortable(),
                TextColumn::make('municipio')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('viagem.id')
                    ->label('Viagem ID')
                    ->searchable(),
            ])
            ->defaultGroup('data_emissao')
            ->groups(
                [
                    Group::make('data_emissao')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn(Models\DocumentoFrete $record): string => Carbon::parse($record->data_emissao)->format('d/m/Y'))
                        ->collapsible(),
                    Group::make('documento_transporte')
                        ->label('Documento Transporte')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                ]
            )
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn($query) => $query
                            ->whereNull('resultado_periodo_id')
                            ->where('veiculo_id', $this->ownerRecord->veiculo_id)
                            ->orderBy('data_emissao', 'desc')
                    )
                    ->recordTitle(
                        fn($record) =>
                        "#{$record->id} | " .
                            Carbon::parse($record->data_emissao)->format('d/m/Y') . " | Nº " .
                            number_format($record->numero_documento, 0, ',', '.') . " | " .
                            $record->tipo_documento->value . " | " .
                            "R$ " . number_format($record->valor_liquido, 2, ',', '.')
                    )
                    ->multiple()
                    ->recordSelectSearchColumns(['id', 'numero_documento', 'documento_transporte', 'parceiro_origem', 'parceiro_destino'])
                    ->label('Vincular Documentos de Frete'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DissociateAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
