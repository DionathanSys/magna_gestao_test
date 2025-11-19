<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AlertaIntegradosViagem extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $cargas;
    public string $dataProcessamento;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $cargas)
    {
        $this->cargas = $cargas; // Recebe as cargas
        $this->dataProcessamento = now()->format('d/m/Y H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalCargas = $this->cargas->count();
        $totalIntegrados = $this->cargas->pluck('integrado_id')->unique()->count();
        
        return new Envelope(
            subject: "⚠️ Alerta: {$totalCargas} Viagens para {$totalIntegrados} Integrados com Alerta",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.alerta-integrados-viagem',
            with: [
                'dados' => $this->prepararDados(),
            ]
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

    /**
     * Prepara os dados agrupados para o email
     */
    private function prepararDados(): array
    {
        $agrupadoPorIntegrado = $this->cargas
            ->groupBy('integrado_id')
            ->map(function ($cargas, $integradoId) {
                $integrado = $cargas->first()->integrado;
                
                return [
                    'integrado' => [
                        'id' => $integrado->id,
                        'codigo' => $integrado->codigo,
                        'nome' => $integrado->nome,
                        'municipio' => $integrado->municipio,
                        'cliente' => $integrado->cliente,
                    ],
                    'viagens' => $cargas->map(function ($carga) {
                        return [
                            'id' => $carga->viagem->id,
                            'numero_viagem' => $carga->viagem->numero_viagem,
                            'documento_transporte' => $carga->viagem->documento_transporte,
                            'veiculo_placa' => $carga->viagem->veiculo->placa ?? 'N/A',
                            'data_competencia' => $carga->viagem->data_competencia,
                            'data_inicio' => $carga->viagem->data_inicio,
                            'km_rodado' => $carga->viagem->km_rodado ?? 0,
                            'km_dispersao' => $carga->km_dispersao ?? 0,
                        ];
                    })->values()->toArray(),
                    'total_viagens' => $cargas->count(),
                ];
            })
            ->sortByDesc('total_viagens')
            ->values()
            ->toArray();

        return [
            'integrados' => $agrupadoPorIntegrado,
            'total_integrados' => count($agrupadoPorIntegrado),
            'total_viagens' => $this->cargas->count(),
            'data_processamento' => $this->dataProcessamento,
        ];
    }
}
