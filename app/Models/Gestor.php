<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestor extends Model
{
    protected $table = 'gestores';

    public function indicadores(): BelongsToMany
    {
        return $this->belongsToMany(Indicador::class, 'gestor_indicador', 'gestor_id', 'indicador_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'gestor_id');
    }

    public function pontuacaoObtida(): Attribute
    {

        return Attribute::make(
            get: function () {
                return $this->resultados->sum('pontuacao_obtida');
            }
        );
    }

    public function pontuacaoMaxima(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->resultados->sum('pontuacao_maxima');
            }
        );
    }

    public function pontuacaoIndividual(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->resultados()
                    ->whereHas('indicador  ', function ($query) {
                        $query->where('tipo', 'INDIVIDUAL');
                    })
                    ->sum('pontuacao');
            }
        );
    }

    public function pontuacaoColetiva(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->resultados()
                    ->whereHas('indicador  ', function ($query) {
                        $query->where('tipo', 'COLETIVO');
                    })
                    ->sum('pontuacao');
            }
        );
    }
}

