<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemSankhya extends Model
{
    protected $table = 'ordens_sankhya';

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }
}
