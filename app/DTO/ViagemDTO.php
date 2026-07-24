<?php

namespace App\DTO;

use App\Models\Integrado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

readonly class ViagemDTO
{
    public function __construct(
        public string $numero_viagem,
        public ?string $numero_custo_frete = null,
        public ?string $documento_transporte = null,
        public ?string $tipo_viagem = null,
        public ?int $veiculo_id = null,
        public ?Integrado $integrado = null,
        public ?float $valor_frete = 0,
        public float $valor_cte = 0,
        public float $valor_nfs = 0,
        public float $valor_icms = 0,
        public float $km_rodado = 0,
        public float $km_pago = 0,
        public float $km_divergencia = 0,
        public float $km_cadastro = 0,
        public float $km_pago_excedente = 0,
        public float $km_rodado_excedente = 0,
        public float $peso = 0,
        public float $entregas = 0,
        public string $data_competencia = '',
        public string $data_inicio = '',
        public string $data_fim = '',
        public array $divergencias = [],
        public int $created_by = 0,
        public int $updated_by = 0,

    ) {}

    public static function makeFromArray(array $data): self
    {
        try {
            $viagemDto = new self(
                numero_viagem: $data['numero_viagem'],
                numero_custo_frete: null,
                documento_transporte: $data['documento_transporte'] ?? null,
                tipo_viagem: $data['tipo_viagem'] ?? 'SIMPLES',
                veiculo_id: $data['veiculo_id'] ?? null,
                integrado: $data['integrado'] ?? null,
                valor_frete: 0,
                valor_cte: 0,
                valor_nfs: 0,
                valor_icms: 0,
                km_rodado: $data['km_rodado'] ?? 0,
                km_pago: $data['km_pago'] ?? 0,
                km_divergencia: $data['km_divergencia'],
                km_cadastro: $data['integrado']->km_rota ?? 0,
                km_pago_excedente: $data['km_pago_excedente'],
                km_rodado_excedente: $data['km_rodado_excedente'],
                peso: 0,
                entregas: 0,
                data_competencia: $data['data_competencia'],
                data_inicio: Carbon::createFromFormat('d/m/Y H:i', $data['data_inicio'])->format('Y-m-d H:i'),
                data_fim: Carbon::createFromFormat('d/m/Y H:i', $data['data_fim'])->format('Y-m-d H:i'),
                divergencias: [],
                created_by: $data['created_by'] ?? 0,
                updated_by: $data['updated_by'] ?? 0,
            );
        } catch (\Exception $e) {
            Log::error('Erro ao criar ViagemDTO', [
                'data' => $data,
            ]);
            throw new \InvalidArgumentException('Dados inválidos para criar ViagemDTO: '.$e->getMessage());
        }

        return $viagemDto;

    }

    public function toArray(): array
    {
        return [
            'numero_viagem' => $this->numero_viagem,
            'numero_custo_frete' => $this->numero_custo_frete,
            'documento_transporte' => $this->documento_transporte,
            'tipo_viagem' => $this->tipo_viagem,
            'veiculo_id' => $this->veiculo_id,
            'valor_frete' => $this->valor_frete,
            'valor_cte' => $this->valor_cte,
            'valor_nfs' => $this->valor_nfs,
            'valor_icms' => $this->valor_icms,
            'km_rodado' => $this->km_rodado,
            'km_pago' => $this->km_pago,
            'km_divergencia' => $this->km_divergencia,
            'km_cadastro' => $this->km_cadastro,
            'km_pago_excedente' => $this->km_pago_excedente,
            'km_rodado_excedente' => $this->km_rodado_excedente,
            'peso' => $this->peso,
            'entregas' => $this->entregas,
            'data_competencia' => $this->data_competencia,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
            'divergencias' => $this->divergencias,
        ];
    }
}
