<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Filament\Resources\Pneus\Actions\EnviarRecapagemPneuAction;
use App\Filament\Resources\Pneus\Actions\ReceberRecapagemPneuAction;
use App\Filament\Resources\Pneus\Actions\RetornarConsertoPneuAction;
use App\Filament\Resources\Pneus\Actions\ReverterRecapagemPneuAction;
use App\Filament\Resources\Pneus\PneuResource;
use App\Filament\Resources\Pneus\Schemas\PneuForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditPneu extends EditRecord
{
    protected static string $resource = PneuResource::class;

    public function form(Schema $schema): Schema
    {
        return PneuForm::configureForEdit($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            RetornarConsertoPneuAction::make(),
            EnviarRecapagemPneuAction::make(),
            ReceberRecapagemPneuAction::make(),
            ReverterRecapagemPneuAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['numero_fogo']);

        return $data;
    }

    // public function hasCombinedRelationManagerTabsWithContent(): bool
    // {
    //     return true;
    // }

    // public function getContentTabLabel(): ?string
    // {
    //     return 'Pneu';
    // }
}
