<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VeiculoDocumento extends Model
{
    use SoftDeletes;

    public const TIPO_TESTE_FUMACA = 'teste_fumaca';

    public const TIPO_AFERICAO_TACOGRAFO = 'afericao_tacografo';

    public const TIPO_OUTROS = 'outros';

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'dias_alerta' => 'integer',
        'anexos' => 'array',
    ];

    protected $appends = [
        'dias_restantes',
        'status_documento',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    protected function diasRestantes(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->data_fim
                ? (int) now()->startOfDay()->diffInDays($this->data_fim->copy()->startOfDay(), false)
                : null,
        );
    }

    protected function statusDocumento(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->data_fim) {
                    return 'Sem vencimento';
                }

                return match (true) {
                    $this->dias_restantes < 0 => 'Vencido',
                    $this->dias_restantes === 0 => 'Vence hoje',
                    $this->dias_restantes <= $this->dias_alerta => 'Em alerta',
                    default => 'Vigente',
                };
            },
        );
    }

    public function getStatusColor(): string
    {
        return match ($this->status_documento) {
            'Vencido' => 'danger',
            'Vence hoje', 'Em alerta' => 'warning',
            'Vigente' => 'success',
            default => 'gray',
        };
    }

    public static function tipoOptions(): array
    {
        return [
            self::TIPO_TESTE_FUMACA => 'Teste de Fumaça',
            self::TIPO_AFERICAO_TACOGRAFO => 'Aferição Tacógrafo',
            self::TIPO_OUTROS => 'Outros',
        ];
    }

    public function getTipoLabel(): string
    {
        return self::tipoOptions()[$this->tipo] ?? $this->tipo;
    }
}
