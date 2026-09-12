<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultadoPeriodoDashboardShareMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $destinatarioNome,
        public string $url,
        public string $periodo,
        public string $expiraEm,
        public int $quantidadeResultados,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dashboard de resultados - '.$this->periodo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.resultado-periodo-dashboard-share',
            with: [
                'destinatarioNome' => $this->destinatarioNome,
                'url' => $this->url,
                'periodo' => $this->periodo,
                'expiraEm' => $this->expiraEm,
                'quantidadeResultados' => $this->quantidadeResultados,
            ],
        );
    }
}
