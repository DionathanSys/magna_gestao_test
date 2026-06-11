<?php

namespace App\Filament\Resources\CteEmailRequests;

use App\Filament\Resources\CteEmailRequests\Pages\ListCteEmailRequests;
use App\Filament\Resources\CteEmailRequests\Pages\ViewCteEmailRequest;
use App\Filament\Resources\CteEmailRequests\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\CteEmailRequests\Schemas\CteEmailRequestInfolist;
use App\Filament\Resources\CteEmailRequests\Tables\CteEmailRequestsTable;
use App\Models\CteEmailRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CteEmailRequestResource extends Resource
{
    protected static ?string $model = CteEmailRequest::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automacoes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $modelLabel = 'Solicitação CTe';

    protected static ?string $pluralModelLabel = 'Solicitações CTe';

    public static function getNavigationBadge(): ?string
    {
        try {
            $pending = CteEmailRequest::query()
                ->whereIn('status', ['pending_send', 'sent', 'response_received', 'processing'])
                ->count();

            return $pending > 0 ? (string) $pending : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function infolist(Schema $schema): Schema
    {
        return CteEmailRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CteEmailRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCteEmailRequests::route('/'),
            'view' => ViewCteEmailRequest::route('/{record}'),
        ];
    }
}
