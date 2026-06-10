<?php

namespace App\Services\Bugio;

use App\DTO\PayloadCteDTO;
use App\Mail\SolicitacaoCteMail;
use App\Models\CteEmailRequest;
use App\Models\CteEmailRequestMessage;
use App\Models\IncomingEmail;
use Illuminate\Support\Str;

class CteEmailRequestService
{
    public function createPendingRequest(PayloadCteDTO $payload, SolicitacaoCteMail $mail): CteEmailRequest
    {
        $correlationCode = $this->generateCorrelationCode();
        $outboundMessageId = $this->generateOutboundMessageId($correlationCode);

        $mail->applyTracking($correlationCode, $outboundMessageId);

        return CteEmailRequest::query()->create([
            'viagem_id' => $payload->viagemId,
            'integrado_id' => $payload->integradoId,
            'documento_transporte' => $payload->documentoTransporte,
            'correlation_code' => $correlationCode,
            'outbound_message_id' => $outboundMessageId,
            'tipo_documento_solicitado' => $payload->cte_complementar ? 'CTe Complemento' : 'CTe',
            'status' => 'pending_send',
            'sent_subject' => $mail->getRenderedSubject(),
            'sent_to' => $this->normalizeAddressField($mail->getToAddress()),
            'sent_reply_to' => $this->normalizeAddressField($mail->getReplyToAddress()),
            'sent_cc' => $this->normalizeAddressField($mail->getCcAddress()),
            'requested_at' => now(),
            'created_by' => $payload->userId,
            'payload' => $payload->toArray(),
        ]);
    }

    public function markSent(CteEmailRequest $request): void
    {
        $request->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $request->messages()->create([
            'direction' => 'outbound',
            'message_id' => $request->outbound_message_id,
            'subject' => $request->sent_subject,
            'from_email' => $request->sent_reply_to,
            'status' => 'sent',
            'processed_at' => now(),
            'metadata' => [
                'correlation_code' => $request->correlation_code,
                'sent_to' => $request->sent_to,
                'sent_cc' => $request->sent_cc,
            ],
        ]);
    }

    public function markSendFailed(CteEmailRequest $request, string $errorMessage): void
    {
        $request->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function findOpenByDocumentoTransporte(string $documentoTransporte): ?CteEmailRequest
    {
        return CteEmailRequest::query()
            ->where('documento_transporte', $documentoTransporte)
            ->whereIn('status', $this->openStatuses())
            ->latest('id')
            ->first();
    }

    public function findOpenByCorrelationCode(string $correlationCode): ?CteEmailRequest
    {
        return CteEmailRequest::query()
            ->where('correlation_code', strtoupper(trim($correlationCode)))
            ->whereIn('status', $this->openStatuses())
            ->latest('id')
            ->first();
    }

    public function findOpenByMessageId(string $messageId): ?CteEmailRequest
    {
        $messageId = $this->normalizeMessageId($messageId);

        if (! $messageId) {
            return null;
        }

        return CteEmailRequest::query()
            ->where('outbound_message_id', $messageId)
            ->whereIn('status', $this->openStatuses())
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<int, string>  $messageIds
     */
    public function findOpenByReferencedMessageIds(array $messageIds): ?CteEmailRequest
    {
        $messageIds = collect($messageIds)
            ->map(fn (string $messageId): ?string => $this->normalizeMessageId($messageId))
            ->filter()
            ->values()
            ->all();

        if ($messageIds === []) {
            return null;
        }

        return CteEmailRequest::query()
            ->whereIn('status', $this->openStatuses())
            ->where(function ($query) use ($messageIds): void {
                $query
                    ->whereIn('outbound_message_id', $messageIds)
                    ->orWhereHas('messages', function ($messageQuery) use ($messageIds): void {
                        $messageQuery
                            ->where('direction', 'outbound')
                            ->whereIn('message_id', $messageIds);
                    });
            })
            ->latest('id')
            ->first();
    }

    public function registerInboundMessage(CteEmailRequest $request, IncomingEmail $incomingEmail, string $matchedBy): CteEmailRequestMessage
    {
        $message = CteEmailRequestMessage::query()->updateOrCreate(
            [
                'cte_email_request_id' => $request->id,
                'incoming_email_id' => $incomingEmail->id,
            ],
            [
                'direction' => 'inbound',
                'message_id' => $incomingEmail->message_id,
                'in_reply_to' => $incomingEmail->in_reply_to,
                'references_header' => $incomingEmail->references_header,
                'from_email' => $incomingEmail->from_email,
                'subject' => $incomingEmail->subject,
                'matched_by' => $matchedBy,
                'status' => 'matched',
                'metadata' => [
                    'correlation_code' => $request->correlation_code,
                    'attachments_count' => $incomingEmail->attachments()->count(),
                ],
            ]
        );

        $request->update([
            'status' => 'response_received',
            'last_response_at' => now(),
            'error_message' => null,
        ]);

        return $message;
    }

    public function markProcessing(CteEmailRequest $request): void
    {
        $request->update([
            'status' => 'processing',
            'error_message' => null,
        ]);
    }

    public function markCompleted(CteEmailRequest $request): void
    {
        $request->update([
            'status' => 'completed',
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed(CteEmailRequest $request, string $errorMessage): void
    {
        $request->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    protected function normalizeAddressField(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(function (mixed $row): string {
                    if (is_array($row)) {
                        return trim((string) ($row['email'] ?? ''));
                    }

                    return trim((string) $row);
                })
                ->filter()
                ->implode(', ');
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    protected function openStatuses(): array
    {
        return ['pending_send', 'sent', 'response_received', 'processing', 'failed'];
    }

    protected function generateCorrelationCode(): string
    {
        return 'CTE-REQ-'.Str::upper((string) Str::ulid());
    }

    protected function generateOutboundMessageId(string $correlationCode): string
    {
        return sprintf('cte-request-%s@%s', Str::lower($correlationCode), $this->resolveMessageIdDomain());
    }

    protected function resolveMessageIdDomain(): string
    {
        $mailFrom = (string) config('mail.from.address');
        $mailDomain = str_contains($mailFrom, '@') ? substr(strrchr($mailFrom, '@'), 1) : null;

        $domain = $mailDomain ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost.localdomain';
        $domain = strtolower(trim((string) $domain));

        return $domain !== '' ? $domain : 'localhost.localdomain';
    }

    protected function normalizeMessageId(?string $messageId): ?string
    {
        $messageId = trim((string) $messageId);

        if ($messageId === '') {
            return null;
        }

        return trim($messageId, "<> \t\n\r\0\x0B");
    }
}
