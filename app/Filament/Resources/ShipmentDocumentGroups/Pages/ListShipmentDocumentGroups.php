<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Pages;

use App\Filament\Resources\ShipmentDocumentGroups\ShipmentDocumentGroupResource;
use App\Services\Filament\StatusTabCountService;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListShipmentDocumentGroups extends ListRecords
{
    protected static string $resource = ShipmentDocumentGroupResource::class;

    private const LAST_ACTIVE_TAB_SESSION_KEY = 'shipment_document_groups_last_active_tab';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = StatusTabCountService::getShipmentDocumentGroupCounts();

        return [
            'todos' => Tab::make('Todos')->badge(array_sum($counts)),
            'matched' => Tab::make('Pareados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'matched'))
                ->badge($counts['matched'] ?? 0),
            'pending_data' => Tab::make('Pendentes')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending_data'))
                ->badge($counts['pending_data'] ?? 0),
            'trip_created' => Tab::make('Viagem criada')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'trip_created'))
                ->badge($counts['trip_created'] ?? 0),
            'failed' => Tab::make('Falhou')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'failed'))
                ->badge($counts['failed'] ?? 0),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        $lastActiveTab = session(self::LAST_ACTIVE_TAB_SESSION_KEY);

        if ($lastActiveTab && array_key_exists($lastActiveTab, $this->getTabs())) {
            return $lastActiveTab;
        }

        return 'todos';
    }

    public function updatedActiveTab(): void
    {
        parent::updatedActiveTab();

        session([self::LAST_ACTIVE_TAB_SESSION_KEY => $this->activeTab]);
    }
}
