<?php

namespace App\Services\Telegram;

use App\Jobs\SendTelegramAlertJob;
use App\Models\CteEmailRequest;
use App\Models\User;
use App\Models\Viagem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ViagemBugioTelegramAlertService
{
    private const RECIPIENT_EMAIL = 'dionathan.silva@transmagnabosco.com.br';

    public function dispatch(Viagem $viagem, ?CteEmailRequest $request, ?string $reason = null): void
    {
        if (! (bool) config('services.telegram.enabled', false) || blank(config('services.telegram.bot_token'))) {
            return;
        }

        $user = User::query()->where('email', self::RECIPIENT_EMAIL)->first()
            ?? User::query()->where('name', 'like', '%Dionathan%')->first();

        if (! $user || blank($user->telegram_chat_id)) {
            Log::warning('Alerta de viagem Bugio não enviado: usuário Dionathan sem Telegram configurado', [
                'viagem_id' => $viagem->id,
            ]);

            return;
        }

        $viagem->loadMissing([
            'veiculo',
            'cargas.integrado',
            'attachments.receivedFiscalDocument',
        ]);
        $request?->loadMissing('integrado');

        $integrado = $request?->integrado
            ?? $viagem->cargas->map(fn ($carga) => $carga->integrado)->filter()->first();
        $payload = $request?->payload ?? [];
        $notas = $payload['nro_notas']
            ?? $viagem->attachments
                ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->numero_nota)
                ->filter()
                ->unique()
                ->values()
                ->all();
        $motorista = data_get($payload, 'motorista.nome')
            ?: data_get($viagem->veiculo?->informacoes_complementares, 'motorista_padrao_cte_cpf');
        $kmRota = data_get($payload, 'km_total', $integrado?->km_rota ?? 0);
        $valorFrete = data_get($payload, 'valor_frete', (float) $kmRota * (float) db_config('config-bugio.valor-quilometro', 0));
        $cancelExpiresAt = $request?->scheduled_at?->copy()->addHour();
        $minimumCancelExpiry = now()->addMinutes(15);
        if (! $cancelExpiresAt || $cancelExpiresAt->lessThan($minimumCancelExpiry)) {
            $cancelExpiresAt = $minimumCancelExpiry;
        }
        $cancelUrl = $request?->origin === CteEmailRequest::ORIGIN_MAIL_INBOUND
            && $request->status === 'pending_send'
            ? URL::temporarySignedRoute(
                'cte-email-requests.cancel',
                $cancelExpiresAt,
                ['cteEmailRequest' => $request->id],
            )
            : null;

        $status = match ($request?->status) {
            'pending_send' => 'Pendente de envio',
            'sending' => 'Em envio',
            'sent' => 'Enviado',
            'response_received' => 'Resposta recebida',
            'processing' => 'Processando',
            'completed' => 'Concluído',
            'failed' => 'Falhou',
            'cancelled' => 'Cancelado',
            default => 'Não agendado',
        };

        $message = '<b>Nova viagem Bugio criada</b>'
            ."\n\n<b>Viagem:</b> ".e($viagem->numero_viagem ?? 'N/A')
            ."\n<b>Documento transporte:</b> ".e($viagem->documento_transporte ?? 'N/A')
            ."\n<b>Placa:</b> ".e($viagem->veiculo?->placa ?? 'N/A')
            ."\n<b>Integrado:</b> ".e($integrado?->nome ?? 'N/A')
            ."\n<b>Destino:</b> ".e(trim(($integrado?->municipio ?? '').' - '.($integrado?->estado ?? ''), ' -') ?: 'N/A')
            ."\n<b>NF-e:</b> ".e($notas === [] ? 'N/A' : implode(', ', $notas))
            ."\n<b>Motorista:</b> ".e($motorista ?: 'N/A')
            ."\n<b>KM rota:</b> ".e(number_format((float) $kmRota, 2, ',', '.'))
            ."\n<b>Valor frete:</b> R$ ".e(number_format((float) $valorFrete, 2, ',', '.'))
            ."\n<b>Solicitação CTe:</b> ".e($request ? '#'.$request->id.' - '.$status : $status);

        if ($request?->scheduled_at) {
            $message .= "\n<b>Previsão de envio:</b> ".e($request->scheduled_at->format('d/m/Y H:i'));
        }

        if ($reason) {
            $message .= "\n<b>Motivo:</b> ".e($reason);
        }

        $alert = DB::transaction(function () use ($user, $viagem, $message, $cancelUrl): mixed {
            $alert = $viagem->telegramAlerts()->create([
                'created_by' => $user->id,
                'message_html' => $message,
                'telegram_options' => [
                    'disable_web_page_preview' => true,
                    'buttons' => $cancelUrl ? [[
                        'text' => 'Abortar solicitação de CTe',
                        'url' => $cancelUrl,
                    ]] : [],
                ],
                'status' => 'pending',
            ]);

            $alert->recipients()->create([
                'user_id' => $user->id,
                'chat_id' => $user->telegram_chat_id,
                'status' => 'pending',
            ]);

            return $alert;
        });

        SendTelegramAlertJob::dispatch($alert->id)
            ->onQueue((string) config('services.telegram.queue', 'integracoes'));
    }
}
