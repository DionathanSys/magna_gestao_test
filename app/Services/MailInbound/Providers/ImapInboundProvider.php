<?php

namespace App\Services\MailInbound\Providers;

use App\Contracts\MailInboundProvider;
use App\Services\MailInbound\Data\InboundAttachmentData;
use App\Services\MailInbound\Data\InboundMessageData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Message;

class ImapInboundProvider implements MailInboundProvider
{
    public function fetchNewMessages(): iterable
    {
        $folder = $this->connectFolder();

        $messages = $folder
            ->query()
            ->unseen()
            ->limit((int) config('mail-inbound.imap.fetch_limit', 20))
            ->get();

        return $messages->map(fn (Message $message) => $this->mapMessage($message));
    }

    public function fetchMessageByExternalId(string|int $externalId): ?InboundMessageData
    {
        $folder = $this->connectFolder();

        $message = $folder->query()->getMessageByUid($externalId);

        return $message ? $this->mapMessage($message) : null;
    }

    protected function mapMessage(Message $message): InboundMessageData
    {
        $from = $message->getFrom()->first();
        $receivedAt = $this->parseReceivedAt($message);
        $inReplyTo = $this->normalizeHeaderValue($message->getInReplyTo()?->first());
        $references = $this->normalizeHeaderList($message->getReferences()?->all() ?? []);

        return new InboundMessageData(
            provider: 'imap',
            externalId: $message->getUid(),
            messageId: $message->getMessageId(),
            fromEmail: $from?->mail,
            fromName: $this->decodeMimeHeader($from?->personal),
            subject: $this->decodeMimeHeader($message->getSubject()),
            receivedAt: $receivedAt,
            headers: [
                'uid' => $message->getUid(),
                'message_id' => $message->getMessageId(),
                'in_reply_to' => $inReplyTo,
                'references' => $references,
            ],
            attachments: $this->mapAttachments($message),
            sourceMessage: $message,
        );
    }

    /**
     * @return array<int, InboundAttachmentData>
     */
    protected function mapAttachments(Message $message): array
    {
        return $message->getAttachments()
            ->map(function ($attachment) {
                return new InboundAttachmentData(
                    filename: (string) ($attachment->getName() ?: $attachment->getFilename() ?: 'attachment.bin'),
                    content: (string) $attachment->getContent(),
                    mimeType: $attachment->getMimeType(),
                    size: $attachment->getSize() ? (int) $attachment->getSize() : null,
                );
            })
            ->all();
    }

    protected function parseReceivedAt(Message $message): ?Carbon
    {
        $date = $message->getDate();

        if (is_object($date) && method_exists($date, 'first')) {
            $date = $date->first();
        }

        if ($date === null || $date === '') {
            return null;
        }

        return Carbon::parse((string) $date);
    }

    protected function normalizeHeaderValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? trim($value, "<> \t\n\r\0\x0B") : null;
    }

    protected function decodeMimeHeader(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        if (! str_contains($value, '=?')) {
            return $value;
        }

        $decoded = iconv_mime_decode($value, 0, 'UTF-8');

        return $decoded !== false ? $decoded : $value;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function normalizeHeaderList(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value): ?string => $this->normalizeHeaderValue($value))
            ->filter()
            ->values()
            ->all();
    }

    public function markAsSeen(mixed $sourceMessage): void
    {
        if (! $sourceMessage instanceof Message) {
            return;
        }

        try {
            $sourceMessage->setFlag('Seen');
        } catch (\Throwable $exception) {
            Log::warning('Falha ao marcar email IMAP como lido', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function connectFolder(): mixed
    {
        $client = Client::make([
            'host' => config('mail-inbound.imap.host'),
            'port' => config('mail-inbound.imap.port'),
            'encryption' => config('mail-inbound.imap.encryption'),
            'validate_cert' => config('mail-inbound.imap.validate_cert'),
            'username' => config('mail-inbound.imap.username'),
            'password' => config('mail-inbound.imap.password'),
            'protocol' => config('mail-inbound.imap.protocol'),
        ]);

        $client->connect();

        return $client->getFolder(config('mail-inbound.imap.folder'));
    }
}
