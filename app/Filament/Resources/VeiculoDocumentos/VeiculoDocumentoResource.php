<?php

namespace App\Filament\Resources\VeiculoDocumentos;

use App\Filament\Resources\VeiculoDocumentos\Pages\CreateVeiculoDocumento;
use App\Filament\Resources\VeiculoDocumentos\Pages\EditVeiculoDocumento;
use App\Filament\Resources\VeiculoDocumentos\Pages\ListVeiculoDocumentos;
use App\Filament\Resources\VeiculoDocumentos\Schemas\VeiculoDocumentoForm;
use App\Filament\Resources\VeiculoDocumentos\Tables\VeiculoDocumentosTable;
use App\Models\VeiculoDocumento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class VeiculoDocumentoResource extends Resource
{
    protected static ?string $model = VeiculoDocumento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Veículos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Documento de Veículo';

    protected static ?string $pluralModelLabel = 'Documentos de Veículos';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return VeiculoDocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VeiculoDocumentosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVeiculoDocumentos::route('/'),
            'create' => CreateVeiculoDocumento::route('/create'),
            'edit' => EditVeiculoDocumento::route('/{record}/edit'),
        ];
    }
}
