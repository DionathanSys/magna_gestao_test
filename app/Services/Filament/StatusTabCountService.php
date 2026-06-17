<?php

namespace App\Services\Filament;

use App\Models\CteEmailRequest;
use App\Models\IncomingEmail;
use App\Models\ShipmentDocumentGroup;
use Illuminate\Support\Facades\Cache;

class StatusTabCountService
{
    private const CACHE_TTL = 300;

    public static function getCteEmailRequestCounts(): array
    {
        return Cache::remember('filament.cte_email_requests.status_counts', self::CACHE_TTL, function (): array {
            return CteEmailRequest::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        });
    }

    public static function getIncomingEmailCounts(): array
    {
        return Cache::remember('filament.incoming_emails.status_counts', self::CACHE_TTL, function (): array {
            return IncomingEmail::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        });
    }

    public static function getShipmentDocumentGroupCounts(): array
    {
        return Cache::remember('filament.shipment_document_groups.status_counts', self::CACHE_TTL, function (): array {
            return ShipmentDocumentGroup::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($count) => (int) $count)
                ->toArray();
        });
    }
}
