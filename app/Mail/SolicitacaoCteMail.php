<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitacaoCteMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $toAddress;
    protected $replyToAddress;
    protected $ccAddress;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->toAddress = db_config('config-bugio.email', 'dionathan.transmagnabosco.com.br');
        $this->replyToAddress = db_config('config-bugio.email-retorno', 'dionathan.transmagnabosco.com.br');
        $this->ccAddress = db_config('config-bugio.emails-copia', '');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitação CT-e Magnabosco - Bugio',
            to: $this->toAddress,
            replyTo: $this->replyToAddress,
            cc: $this->ccAddress,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.solicitacao-cte-mail',

        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
