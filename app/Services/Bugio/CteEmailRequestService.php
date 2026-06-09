<?php

namespace App\Services\Bugio;

use App\DTO\PayloadCteDTO;
use App\Mail\SolicitacaoCteMail;
use App\Models\CteEmailRequest;
use App\Models\CteEmailRequestMessage;
use App\Models\IncomingEmail;

class CteEmailRequestService
{
    public function createPendingRequest(PayloadCteDTO $payload, SolicitacaoCteMail $mail): CteEmailRequest
    {
        return CteEmailRequest::query()->create([
            'viagem_id' => $payload->viagemId,
            'integrado_id' => $payload->integradoId,
            'documento_transporte' => $payload->documentoTransporte,
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
            'subject' => $request->sent_subject,
            'from_email' => $request->sent_reply_to,
            'status' => 'sent',
            'processed_at' => now(),
            'metadata' => [
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
            ->whereIn('status', ['pending_send', 'sent', 'response_received', 'processing', 'failed'])
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
                'from_email' => $incomingEmail->from_email,
                'subject' => $incomingEmail->subject,
                'matched_by' => $matchedBy,
                'status' => 'matched',
                'metadata' => [
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
}
