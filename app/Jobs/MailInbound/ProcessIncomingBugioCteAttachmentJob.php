<?php

namespace App\Jobs\MailInbound;

use App\Models\CteEmailRequestMessage;
use App\Models\IncomingEmailAttachment;
use App\Services\Bugio\CteReturnDocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingBugioCteAttachmentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $cteEmailRequestMessageId,
        public int $incomingEmailAttachmentId,
    ) {}

    public function handle(CteReturnDocumentService $service): void
    {
        $message = CteEmailRequestMessage::query()->findOrFail($this->cteEmailRequestMessageId);
        $request = $message->request()->with(['integrado', 'viagem'])->firstOrFail();
        $attachment = IncomingEmailAttachment::query()->findOrFail($this->incomingEmailAttachmentId);

        try {
            $service->processAttachment($request, $attachment);

            $message->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $message->update([
                'status' => 'failed',
                'processed_at' => now(),
                'metadata' => [
                    ...($message->metadata ?? []),
                    'error_message' => $exception->getMessage(),
                ],
            ]);

            throw $exception;
        }
    }
}
