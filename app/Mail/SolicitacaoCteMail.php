<?php

namespace App\Mail;

use App\DTO\PayloadCteDTO;
use App\Services\Bugio\CteEmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SolicitacaoCteMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $toAddress;

    protected $replyToAddress;

    protected $ccAddress;

    protected string $renderedSubject;

    protected string $renderedBody;

    protected ?string $correlationCode = null;

    protected ?string $messageId = null;

    /**
     * Create a new message instance.
     */
    public function __construct(public PayloadCteDTO $payload)
    {
        $this->toAddress = db_config('config-bugio.email', 'dionathan.transmagnabosco.com.br');
        $this->replyToAddress = db_config('config-bugio.email-retorno', 'dionathan.transmagnabosco.com.br');
        $this->ccAddress = db_config('config-bugio.emails-copia', '');

        $templateService = app(CteEmailTemplateService::class);
        $this->renderedSubject = $templateService->renderSubject($this->payload);
        $this->renderedBody = $templateService->renderBody($this->payload);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getRenderedSubject(),
            to: $this->toAddress,
            replyTo: $this->replyToAddress,
            cc: $this->ccAddress,
            bcc: 'suporte@axionsoft.com.br'
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
            text: array_filter([
                'X-Cte-Correlation-Code' => $this->correlationCode,
                'X-Cte-Documento-Transporte' => $this->payload->documentoTransporte,
            ])
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.solicitacao-cte-mail',
            with: ['body' => $this->renderedBody],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->payload->anexos as $index => $anexo) {

            try {
                $attachments[] = Attachment::fromStorageDisk('local', $anexo);

            } catch (\Exception $e) {
                Log::error('Erro ao anexar arquivo no email', [
                    'error' => $e->getMessage(),
                    'anexo' => $anexo,
                ]);
            }
        }

        return $attachments;
    }

    public function getRenderedSubject(): string
    {
        if (! $this->correlationCode || str_contains($this->renderedSubject, $this->correlationCode)) {
            return $this->renderedSubject;
        }

        return trim($this->renderedSubject.' ['.$this->correlationCode.']');
    }

    public function getToAddress(): mixed
    {
        return $this->toAddress;
    }

    public function getReplyToAddress(): mixed
    {
        return $this->replyToAddress;
    }

    public function getCcAddress(): mixed
    {
        return $this->ccAddress;
    }

    public function applyTracking(string $correlationCode, string $messageId): self
    {
        $this->correlationCode = $correlationCode;
        $this->messageId = $messageId;

        return $this;
    }
}
