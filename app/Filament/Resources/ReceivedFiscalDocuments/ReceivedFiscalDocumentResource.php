<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments;

use App\Filament\Resources\ReceivedFiscalDocuments\Pages\ListReceivedFiscalDocuments;
use App\Filament\Resources\ReceivedFiscalDocuments\Pages\ViewReceivedFiscalDocument;
use App\Filament\Resources\ReceivedFiscalDocuments\Schemas\ReceivedFiscalDocumentInfolist;
use App\Filament\Resources\ReceivedFiscalDocuments\Tables\ReceivedFiscalDocumentsTable;
use App\Models\ReceivedFiscalDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ReceivedFiscalDocumentResource extends Resource
{
    protected static ?string $model = ReceivedFiscalDocument::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automacoes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $modelLabel = 'Documento Fiscal Recebido';

    protected static ?string $pluralModelLabel = 'Documentos Fiscais Recebidos';

    public static function infolist(Schema $schema): Schema
    {
        return ReceivedFiscalDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceivedFiscalDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceivedFiscalDocuments::route('/'),
            'view' => ViewReceivedFiscalDocument::route('/{record}'),
        ];
    }
}
