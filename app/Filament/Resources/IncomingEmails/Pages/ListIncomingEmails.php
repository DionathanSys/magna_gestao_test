<?php

namespace App\Filament\Resources\IncomingEmails\Pages;

use App\Filament\Resources\IncomingEmails\IncomingEmailResource;
use App\Services\Filament\StatusTabCountService;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIncomingEmails extends ListRecords
{
    protected static string $resource = IncomingEmailResource::class;

    private const LAST_ACTIVE_TAB_SESSION_KEY = 'incoming_emails_last_active_tab';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = StatusTabCountService::getIncomingEmailCounts();

        return [
            'todos' => Tab::make('Todos')->badge(array_sum($counts)),
            'stored' => Tab::make('Capturados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'stored'))
                ->badge($counts['stored'] ?? 0),
            'parsed' => Tab::make('Parseados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'parsed'))
                ->badge($counts['parsed'] ?? 0),
            'processed' => Tab::make('Processados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'processed'))
                ->badge($counts['processed'] ?? 0),
            'ignored' => Tab::make('Ignorados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'ignored'))
                ->badge($counts['ignored'] ?? 0),
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
        session([self::LAST_ACTIVE_TAB_SESSION_KEY => $this->activeTab]);
    }
}
