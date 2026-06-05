<?php

namespace App\Services\MailInbound;

use App\Contracts\MailInboundProvider;
use App\Services\MailInbound\Providers\ImapInboundProvider;
use InvalidArgumentException;

class ProviderRegistry
{
    public function __construct(protected ImapInboundProvider $imapInboundProvider)
    {
    }

    public function resolve(?string $provider = null): MailInboundProvider
    {
        return match ($provider ?? config('mail-inbound.default_provider')) {
            'imap' => $this->imapInboundProvider,
            default => throw new InvalidArgumentException('Provider de entrada não suportado.'),
        };
    }
}
