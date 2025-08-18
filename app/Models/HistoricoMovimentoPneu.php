<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HistoricoMovimentoPneu extends Model
{
    protected $table = 'historico_movimento_pneus';

    public function pneu()
    {
        return $this->belongsTo(Pneu::class);
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function kmPercorrido(): Attribute
    {
        return Attribute::get(
            fn () => ($this->km_final ?? 0) - ($this->km_inicial ?? 0)
        );
    }

    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel');
    }
}
