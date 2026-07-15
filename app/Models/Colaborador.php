<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colaborador extends Model
{
    protected $table = 'colaboradores';

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function apontamentosOficina(): HasMany
    {
        return $this->hasMany(OrdemServicoApontamento::class);
    }
}
