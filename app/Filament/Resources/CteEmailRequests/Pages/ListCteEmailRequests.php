<?php

namespace App\Filament\Resources\CteEmailRequests\Pages;

use App\Filament\Resources\CteEmailRequests\CteEmailRequestResource;
use App\Services\Filament\StatusTabCountService;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCteEmailRequests extends ListRecords
{
    protected static string $resource = CteEmailRequestResource::class;

    private const LAST_ACTIVE_TAB_SESSION_KEY = 'cte_email_requests_last_active_tab';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = StatusTabCountService::getCteEmailRequestCounts();

        return [
            'todos' => Tab::make('Todos')->badge(array_sum($counts)),
            'pending_send' => Tab::make('Pendente envio')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending_send'))
                ->badge($counts['pending_send'] ?? 0),
            'sent' => Tab::make('Enviado')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'sent'))
                ->badge($counts['sent'] ?? 0),
            'response_received' => Tab::make('Resposta recebida')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'response_received'))
                ->badge($counts['response_received'] ?? 0),
            'processing' => Tab::make('Processando')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'processing'))
                ->badge($counts['processing'] ?? 0),
            'completed' => Tab::make('Concluido')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'completed'))
                ->badge($counts['completed'] ?? 0),
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
