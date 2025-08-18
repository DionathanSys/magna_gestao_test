<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integrado extends Model
{
    public function cargas(): HasMany
    {
        return $this->hasMany(CargaViagem::class, 'integrado_id');
    }
    
}
