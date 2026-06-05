<?php

namespace App\Services\MailInbound;

use App\Events\MailInbound\IncomingEmailStored;
use App\Models\IncomingEmail;
use App\Models\IncomingEmailAttachment;
use App\Services\MailInbound\Data\InboundAttachmentData;
use App\Services\MailInbound\Data\InboundMessageData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InboundMessageIngestionService
{
    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected MailInboundConfig $config,
    ) {
    }

    public function ingest(): void
    {
        if (! $this->config->enabled()) {
            Log::info('Fluxo de ingestao de emails ignorado porque esta desabilitado.');
            return;
        }

        if ($this->config->allowedSenders() === []) {
            Log::warning('Fluxo de ingestao de emails abortado: nenhum remetente permitido configurado.');
            return;
        }

        $provider = $this->providerRegistry->resolve();
        $messages = $provider->fetchNewMessages();

        $messageCount = is_countable($messages) ? count($messages) : null;

        Log::info('Mensagens capturadas para ingestao', [
            'provider' => config('mail-inbound.default_provider'),
            'allowed_senders' => $this->config->allowedSenders(),
            'count' => $messageCount,
        ]);

        foreach ($messages as $message) {
            $this->ingestMessage($message, $provider);
        }
    }

    protected function ingestMessage(InboundMessageData $message, mixed $provider): void
    {
        $fromEmail = strtolower(trim((string) $message->fromEmail));
        $allowedSenders = $this->config->allowedSenders();

        Log::info('Analisando email recebido para ingestao', [
            'message_id' => $message->messageId,
            'external_id' => $message->externalId,
            'from_email' => $fromEmail,
            'subject' => $message->subject,
            'attachments_count' => count($message->attachments),
        ]);

        if (! in_array($fromEmail, $allowedSenders, true)) {
            Log::info('Email ignorado por remetente nao permitido', [
                'from_email' => $fromEmail,
                'allowed_senders' => $allowedSenders,
                'message_id' => $message->messageId,
            ]);
            return;
        }

        $messageId = $message->messageId ?: $this->buildFallbackMessageId($message);

        $incomingEmail = IncomingEmail::query()->firstOrCreate(
            ['message_id' => $messageId],
            [
                'provider' => $message->provider,
                'external_id' => $message->externalId,
                'from_email' => $fromEmail,
                'from_name' => $message->fromName,
                'subject' => $message->subject,
                'received_at' => $message->receivedAt,
                'raw_headers' => $message->headers,
                'status' => 'stored',
                'metadata' => [],
            ]
        );

        if ($incomingEmail->wasRecentlyCreated) {
            Log::info('Novo email persistido para processamento', [
                'incoming_email_id' => $incomingEmail->id,
                'message_id' => $incomingEmail->message_id,
            ]);

            foreach ($message->attachments as $attachment) {
                $this->storeAttachment($incomingEmail, $attachment);
            }

            event(new IncomingEmailStored($incomingEmail->id));
        } else {
            Log::info('Email ja havia sido ingerido anteriormente', [
                'incoming_email_id' => $incomingEmail->id,
                'message_id' => $incomingEmail->message_id,
            ]);
        }

        if (config('mail-inbound.imap.mark_as_seen') && method_exists($provider, 'markAsSeen')) {
            $provider->markAsSeen($message->sourceMessage);
        }
    }

    protected function storeAttachment(IncomingEmail $incomingEmail, InboundAttachmentData $attachment): void
    {
        $disk = config('mail-inbound.storage.disk');
        $basePath = trim((string) config('mail-inbound.storage.path'), '/');
        $directory = sprintf(
            '%s/%s/%s',
            $basePath,
            now()->format('Y/m/d'),
            $incomingEmail->id,
        );

        $originalName = $attachment->filename;
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $sanitizedName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $storedFilename = Str::uuid() . '_' . ($sanitizedName ?: 'attachment') . ($extension ? '.' . $extension : '');
        $path = $directory . '/' . $storedFilename;

        Storage::disk($disk)->put($path, $attachment->content);

        $record = IncomingEmailAttachment::query()->create([
            'incoming_email_id' => $incomingEmail->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedFilename,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $attachment->mimeType,
            'extension' => $extension ?: null,
            'size_bytes' => $attachment->size ?? strlen($attachment->content),
            'checksum' => hash('sha256', $attachment->content),
            'kind' => $this->resolveKind($extension, $attachment->mimeType),
            'status' => 'stored',
            'metadata' => [],
        ]);

        Log::info('Anexo salvo para email recebido', [
            'incoming_email_id' => $incomingEmail->id,
            'attachment_id' => $record->id,
            'original_filename' => $originalName,
            'kind' => $record->kind,
            'path' => $path,
        ]);
    }

    protected function resolveKind(?string $extension, ?string $mimeType): string
    {
        $extension = strtolower((string) $extension);
        $mimeType = strtolower((string) $mimeType);

        return match (true) {
            $extension === 'xml', str_contains($mimeType, 'xml') => 'xml',
            $extension === 'pdf', str_contains($mimeType, 'pdf') => 'pdf',
            default => 'other',
        };
    }

    protected function buildFallbackMessageId(InboundMessageData $message): string
    {
        return sha1(json_encode([
            $message->provider,
            $message->externalId,
            $message->fromEmail,
            $message->subject,
            $message->receivedAt?->toIso8601String(),
        ]));
    }
}
