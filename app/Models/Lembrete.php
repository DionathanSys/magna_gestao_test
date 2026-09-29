<?php

namespace App\Models;

use App\Enum\Lembrete\StatusLembreteEnum;
use App\Enum\Lembrete\TipoLembreteEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembrete extends Model
{
    protected $fillable = [
        'tipo',
        'titulo',
        'mensagem',
        'user_id',
        'scheduled_at',
        'status',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoLembreteEnum::class,
            'status' => StatusLembreteEnum::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', StatusLembreteEnum::PENDENTE->value);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->pending()
            ->where('scheduled_at', '<=', now());
    }
}
