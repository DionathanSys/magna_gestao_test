<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesenhoPneu extends Model
{
    protected $table = 'desenhos_pneu';

    public function pneus(): HasMany
    {
        return $this->hasMany(Pneu::class, 'desenho_pneu_id');
    }
}
