<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integrado extends Model
{
    protected $casts = [
        'cliente' => \App\Enum\ClienteEnum::class,
    ];
    
    public function cargas(): HasMany
    {
        return $this->hasMany(CargaViagem::class, 'integrado_id');
    }

}
