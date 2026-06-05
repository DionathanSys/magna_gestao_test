<?php

namespace App\Services\MailInbound\Data;

use Carbon\CarbonInterface;

readonly class InboundMessageData
{
    /**
     * @param  array<int, InboundAttachmentData>  $attachments
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        public string $provider,
        public ?string $externalId,
        public ?string $messageId,
        public ?string $fromEmail,
        public ?string $fromName,
        public ?string $subject,
        public ?CarbonInterface $receivedAt,
        public array $headers,
        public array $attachments,
        public mixed $sourceMessage = null,
    ) {
    }
}
