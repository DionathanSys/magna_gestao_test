<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResultadoPeriodo extends Model
{
    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function tipoVeiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVeiculo::class, 'tipo_veiculo_id');
    }

    public function abastecimentos(): HasMany
    {
        return $this->hasMany(Abastecimento::class);
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    public function documentosFrete(): HasMany
    {
        return $this->hasMany(DocumentoFrete::class);
    }

    public function abastecimentoInicial(): HasOne
    {
        return $this->hasOne(Abastecimento::class)->ofMany('data_abastecimento', 'min');
    }

    public function abastecimentoFinal(): HasOne
    {
        return dd($this->hasOne(Abastecimento::class)->ofMany('data_abastecimento', 'max'));
    }
    
}
