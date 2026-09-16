<?php

namespace App\Filament\Resources\PlanoPreventivos;

use App\Filament\Resources\PlanoPreventivos\Pages\CreatePlanoPreventivo;
use App\Filament\Resources\PlanoPreventivos\Pages\EditPlanoPreventivo;
use App\Filament\Resources\PlanoPreventivos\Pages\ListPlanoPreventivos;
use App\Filament\Resources\PlanoPreventivos\Pages\ViewPlanoPreventivo;
use App\Filament\Resources\PlanoPreventivos\RelationManagers\ExecucoesRelationManager;
use App\Filament\Resources\PlanoPreventivos\RelationManagers\VeiculosRelationManager;
use App\Filament\Resources\PlanoPreventivos\Schemas\PlanoPreventivoForm;
use App\Filament\Resources\PlanoPreventivos\Schemas\PlanoPreventivoInfolist;
use App\Filament\Resources\PlanoPreventivos\Tables\PlanoPreventivosTable;
use App\Models\PlanoPreventivo;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlanoPreventivoResource extends Resource
{
    protected static ?string $model = PlanoPreventivo::class;

    protected static ?string $slug = 'planos-preventivos';

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Plano Preventivo';

    protected static ?string $pluralModelLabel = 'Planos Preventivos';

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return PlanoPreventivoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlanoPreventivoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanoPreventivosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VeiculosRelationManager::class,
            ExecucoesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlanoPreventivos::route('/'),
            'create' => CreatePlanoPreventivo::route('/create'),
            'view' => ViewPlanoPreventivo::route('/{record}'),
            'edit' => EditPlanoPreventivo::route('/{record}/edit'),
        ];
    }

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->visible(fn (): bool => (bool) auth()->user()?->is_admin)
            ->before(function (DeleteAction $action, PlanoPreventivo $record): void {
                if ($record->veiculos()->exists() || $record->ordensServico()->exists()) {
                    Notification::make()
                        ->title('Plano em uso')
                        ->body('Desative o plano em vez de excluí-lo enquanto houver veículos ou execuções vinculadas.')
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }
}
