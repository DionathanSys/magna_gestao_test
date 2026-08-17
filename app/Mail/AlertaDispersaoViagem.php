<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AlertaDispersaoViagem extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $viagens;

    public float $limiteKm;

    public string $dataProcessamento;

    public function __construct(Collection $viagens, float $limiteKm)
    {
        $this->viagens = $viagens;
        $this->limiteKm = $limiteKm;
        $this->dataProcessamento = now()->format('d/m/Y H:i:s');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Alerta de Dispersão: {$this->viagens->count()} viagens ≥ {$this->limiteKm} km",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.alerta-dispersao-viagem',
            with: [
                'viagens' => $this->viagens,
                'limite_km' => $this->limiteKm,
                'data_processamento' => $this->dataProcessamento,
            ]
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
