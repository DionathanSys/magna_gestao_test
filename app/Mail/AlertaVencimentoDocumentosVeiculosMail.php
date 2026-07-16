<?php

namespace App\Mail;

use App\Models\VeiculoDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AlertaVencimentoDocumentosVeiculosMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $documentos,
        public array $regra,
    ) {}

    public function envelope(): Envelope
    {
        $tipo = VeiculoDocumento::tipoOptions()[$this->regra['tipo'] ?? null] ?? 'Documentos';

        return new Envelope(
            subject: 'Alerta de vencimento - '.$tipo.' ('.$this->documentos->count().')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.alerta-vencimento-documentos-veiculos',
            with: [
                'documentos' => $this->documentos,
                'regra' => $this->regra,
                'tipoLabel' => VeiculoDocumento::tipoOptions()[$this->regra['tipo'] ?? null] ?? ($this->regra['tipo'] ?? 'Documentos'),
                'dataGeracao' => now()->format('d/m/Y H:i'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
