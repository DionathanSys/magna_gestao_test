<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HistoricoMovimentoPneu extends Model
{
    protected $table = 'historico_movimento_pneus';

    protected $casts = [
        'anexos' => 'array',
        'ciclo_vida' => 'integer',
    ];

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PneuCiclo::class, 'pneu_ciclo_id');
    }

    public function posicaoVeiculo(): BelongsTo
    {
        return $this->belongsTo(PneuPosicaoVeiculo::class, 'pneu_posicao_veiculo_id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel');
    }
}
