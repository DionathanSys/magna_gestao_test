<?php

namespace App\Mail;

use App\DTO\PayloadCteDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SolicitacaoCteMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $toAddress;
    protected $replyToAddress;
    protected $ccAddress;

    /**
     * Create a new message instance.
     */
    public function __construct(public PayloadCteDTO $payload)
    {
        $this->toAddress        = db_config('config-bugio.email', 'dionathan.transmagnabosco.com.br');
        $this->replyToAddress   = db_config('config-bugio.email-retorno', 'dionathan.transmagnabosco.com.br');
        $this->ccAddress        = db_config('config-bugio.emails-copia', '');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {

        return new Envelope(
            // subject: 'Solicitação CT-e Magnabosco - Bugio ' . $this->payload->veiculo . ' - ' . implode(', ', $this->payload->nro_notas ?? []) . ' - ' . now()->format('d/m/Y H:i'),
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
        $attachments = [];

        Log::debug(__METHOD__ . '@' . __LINE__, ['anexo' => $this->payload->anexos]);

        foreach ($this->payload->anexos as $anexo) {
            Log::alert('ver o erro aqui?', [
                'anexo' => $anexo,
            ]);

            try {
                // Verificar se é array ou objeto
                if (is_array($anexo)) {
                    $attachments[] = Attachment::fromPath($anexo)
                        ->as($anexo['name'])
                        ->withMime($anexo['mime']);
                } else {
                    // Se for string (path direto)
                    $attachments[] = Attachment::fromStorageDisk('local', $anexo);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao anexar arquivo no email', [
                    'error' => $e->getMessage(),
                    'anexo' => $anexo,
                ]);
            }
        }

        // foreach ($this->payload->anexos as $path) {
        //     try {

        //         if (! is_string($path) || trim($path) === '') {
        //             continue;
        //         }

        //         // Verifica se o arquivo realmente existe no disk
        //         if (! Storage::disk('local')->exists($path)) {
        //             Log::warning('Arquivo de anexo não encontrado', [
        //                 'path' => $path,
        //             ]);
        //             continue;
        //         }

        //         $attachments[] = Attachment::fromStorageDisk('local', $path);
        //     } catch (\Throwable $e) {
        //         Log::error('Erro ao montar anexo de e-mail', [
        //             'path'  => is_string($path) ? $path : gettype($path),
        //             'error' => $e->getMessage(),
        //         ]);
        //     }
        // }

        Log::debug('atta', [
            'atta' => $attachments,
        ]);
        return $attachments;
    }
}
