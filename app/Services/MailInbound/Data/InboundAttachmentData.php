<?php

namespace App\Services\MailInbound\Data;

readonly class InboundAttachmentData
{
    public function __construct(
        public string $filename,
        public string $content,
        public ?string $mimeType = null,
        public ?int $size = null,
    ) {}
}
