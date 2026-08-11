<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebScraperViagensFalhasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $errors) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Falhas na integracao WebScraper de viagens: '.count($this->errors).' erro(s)',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.webscraper-viagens-falhas',
            with: [
                'errors' => $this->errors,
                'generatedAt' => now()->format('d/m/Y H:i:s'),
            ],
        );
    }
}
