<?php

namespace App\Services\Alertas;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TelegramAlertConfigService
{
    public const ORDEM_SERVICO_CRIADA = 'ordem_servico_criada';

    public function isEnabled(string $alertType): bool
    {
        if (! (bool) config('services.telegram.enabled', false) || blank(config('services.telegram.bot_token'))) {
            return false;
        }

        return (bool) db_config(
            'config-alertas.telegram.'.$alertType.'.ativo',
            false,
        );
    }

    /**
     * @return list<int>
     */
    public function recipientIds(string $alertType): array
    {
        return collect(db_config(
            'config-alertas.telegram.'.$alertType.'.destinatarios',
            [],
        ))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(string $alertType): Collection
    {
        $ids = $this->recipientIds($alertType);

        if ($ids === []) {
            return new Collection;
        }

        return User::query()
            ->whereIn('id', $ids)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->get();
    }
}
