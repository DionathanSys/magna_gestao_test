<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ViagemBugiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn () => Auth::user()->is_admin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->is_admin),
                ]),
            ]);
    }
}
