<?php

namespace App\Filament\Resources\IncomingEmails;

use App\Filament\Resources\IncomingEmails\Pages\ListIncomingEmails;
use App\Filament\Resources\IncomingEmails\Pages\ViewIncomingEmail;
use App\Filament\Resources\IncomingEmails\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\IncomingEmails\Schemas\IncomingEmailInfolist;
use App\Filament\Resources\IncomingEmails\Tables\IncomingEmailsTable;
use App\Models\IncomingEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

class IncomingEmailResource extends Resource
{
    protected static ?string $model = IncomingEmail::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automacoes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'Email Capturado';

    protected static ?string $pluralModelLabel = 'Emails Capturados';

    public static function getNavigationBadge(): ?string
    {
        try {
            $pending = IncomingEmail::query()->where('status', 'stored')->count();

            return $pending > 0 ? (string) $pending : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function infolist(Schema $schema): Schema
    {
        return IncomingEmailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncomingEmailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomingEmails::route('/'),
            'view' => ViewIncomingEmail::route('/{record}'),
        ];
    }
}
