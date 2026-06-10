<?php

namespace App\Services\Bugio;

use App\Jobs\MailInbound\ProcessIncomingBugioCteAttachmentJob;
use App\Models\CteEmailRequest;
use App\Models\IncomingEmail;
use Illuminate\Support\Facades\Log;

class CteReturnEmailProcessingService
{
    public function __construct(
        protected CteReturnEmailMatchingService $matchingService,
        protected CteEmailRequestService $requestService,
    ) {}

    public function process(int $incomingEmailId): void
    {
        $incomingEmail = IncomingEmail::query()->with('attachments')->findOrFail($incomingEmailId);
        $match = $this->matchingService->matchWithStrategy($incomingEmail);
        $request = $match['request'] ?? null;

        if (! $request) {
            Log::info('Email recebido nao corresponde a retorno de CT-e Bugio rastreavel', [
                'incoming_email_id' => $incomingEmail->id,
                'from_email' => $incomingEmail->from_email,
                'subject' => $incomingEmail->subject,
                'in_reply_to' => $incomingEmail->in_reply_to,
                'references_header' => $incomingEmail->references_header,
            ]);

            return;
        }

        $message = $this->requestService->registerInboundMessage($request, $incomingEmail, $match['matched_by']);

        foreach ($incomingEmail->attachments as $attachment) {
            ProcessIncomingBugioCteAttachmentJob::dispatch($message->id, $attachment->id)
                ->onQueue(config('mail-inbound.queue.cte_return'));
        }
    }

    public function reprocessRequest(int $requestId): void
    {
        $request = CteEmailRequest::query()
            ->with('messages.incomingEmail.attachments')
            ->findOrFail($requestId);

        foreach ($request->messages->where('direction', 'inbound') as $message) {
            foreach ($message->incomingEmail?->attachments ?? [] as $attachment) {
                ProcessIncomingBugioCteAttachmentJob::dispatch($message->id, $attachment->id)
                    ->onQueue(config('mail-inbound.queue.cte_return'));
            }
        }
    }
}
