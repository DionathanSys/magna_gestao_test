<?php

namespace App\DTO;

use App\Models\Integrado;

class IntegradoCteDTO
{
    private ?Integrado $integrado = null;

    public function __construct(
        public readonly int $integradoId,
        public readonly float $kmRota,
    ) {}

    public function getIntegrado(): ?Integrado
    {
        if ($this->integrado === null) {
            $this->integrado = Integrado::find($this->integradoId);
        }

        return $this->integrado;
    }

    public function getNomeIntegrado(): string
    {
        return $this->getIntegrado()?->nome ?? 'Integrado não encontrado';
    }

    public function getCodigoIntegrado(): string
    {
        return $this->getIntegrado()?->codigo ?? 'N/A';
    }

    public function toArray(): array
    {
        return [
            'integrado_id' => $this->integradoId,
            'km_rota' => $this->kmRota,
            'nome' => $this->getNomeIntegrado(),
            'codigo' => $this->getCodigoIntegrado(),
        ];
    }
}
