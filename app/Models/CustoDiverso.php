<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustoDiverso extends Model
{
    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'custo_total' => 'decimal:2',
        'quantidade_veiculos' => 'integer',
        'descricao' => 'array',
    ];
}
