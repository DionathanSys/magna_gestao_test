<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegradoAlias extends Model
{
    protected $table = 'integrado_aliases';

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class);
    }
}
