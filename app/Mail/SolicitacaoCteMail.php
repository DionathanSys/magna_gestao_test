<?php

namespace App\Mail;

use App\DTO\PayloadCteDTO;
use App\Services\Bugio\CteEmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
            subject: $this->renderedSubject,
            to: $this->toAddress,
            replyTo: $this->replyToAddress,
            cc: $this->ccAddress,
            bcc: 'suporte@axionsoft.com.br'
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
        return $this->renderedSubject;
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
}
