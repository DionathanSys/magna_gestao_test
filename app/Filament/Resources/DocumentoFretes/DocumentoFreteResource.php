<?php

namespace App\Filament\Resources\DocumentoFretes;

use App\Filament\Resources\DocumentoFretes\Pages\CreateDocumentoFrete;
use App\Filament\Resources\DocumentoFretes\Pages\EditDocumentoFrete;
use App\Filament\Resources\DocumentoFretes\Pages\ListDocumentoFretes;
use App\Filament\Resources\DocumentoFretes\Pages\ViewDocumentoFrete;
use App\Filament\Resources\DocumentoFretes\Schemas\DocumentoFreteForm;
use App\Filament\Resources\DocumentoFretes\Schemas\DocumentoFreteInfolist;
use App\Filament\Resources\DocumentoFretes\Tables\DocumentoFretesTable;
use App\Models\DocumentoFrete;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DocumentoFreteResource extends Resource
{
    protected static ?string $model = DocumentoFrete::class;

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static ?string $modelLabel = 'Documento Frete';

    protected static ?string $pluralModelLabel = 'Documentos Frete';

    protected static ?string $recordTitleAttribute = 'documento_transporte';

    public static function form(Schema $schema): Schema
    {
        return DocumentoFreteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentoFreteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentoFretesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentoFretes::route('/'),
            'view' => ViewDocumentoFrete::route('/{record}'),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Placa' => $record->veiculo->placa,
            'Valor' => 'R$ ' . number_format($record->valor_total, 2, ',', '.'),
            'Valor ICMS' => 'R$ ' . number_format($record->valor_icms, 2, ',', '.'),
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->url(static::getUrl('edit', ['record' => $record->id]), shouldOpenInNewTab: true),
        ];
    }
}
