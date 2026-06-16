<?php

namespace App\Models;

use App\Events\Viagem\RecalcularRateioKmDispersaoRequested;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Viagem extends Model
{
    protected $table = 'viagens';

    protected $casts = [
        'pendencias' => 'array',
        'conferido' => 'boolean',
        'ignorar' => 'boolean',
        'integrados_json' => 'array',
        'possui_pendencia' => 'boolean',
        'numero_sequencial' => 'integer',
    ];

    protected $appends = []; // Accessors removidos para evitar N+1/Lazy Loading em listagens

    protected static function booted(): void
    {
        static::updated(function (self $model): void {
            if ($model->isDirty(['km_rodado', 'km_pago'])) {
                RecalcularRateioKmDispersaoRequested::dispatch($model->id, 'viagem_km_updated');
            }
        });
    }

    /**
     * Relação com o modelo ResultadoPeriodo
     */
    public function resultadoPeriodo(): BelongsTo
    {
        return $this->belongsTo(ResultadoPeriodo::class);
    }

    public function cargas(): HasMany
    {
        return $this->hasMany(CargaViagem::class, 'viagem_id');
    }

    public function carga(): HasOne
    {
        return $this->hasOne(CargaViagem::class, 'viagem_id');
    }

    public function integrados(): HasManyThrough
    {
        return $this->hasManyThrough(
            Integrado::class,
            CargaViagem::class,
            'viagem_id', // Foreign key on CargaViagem table
            'id', // Foreign key on Integrado table
            'id', // Local key on Viagem table
            'integrado_id' // Local key on CargaViagem table
        );
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoFrete::class, 'viagem_id', 'id');
    }

    public function cteEmailRequests(): HasMany
    {
        return $this->hasMany(CteEmailRequest::class, 'viagem_id', 'id');
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(AnotacaoViagem::class, 'viagem_id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function viagemBugio(): HasMany
    {
        return $this->hasMany(ViagemBugio::class, 'viagem_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ViagemAttachment::class, 'viagem_id');
    }

    protected function mapsIntegrados(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $origin = '-27.0927894,-52.6491463';

                // ✅ Verifica se cargas já foram carregadas
                if (! $this->relationLoaded('cargas')) {
                    // Carrega sob demanda se necessário
                    $this->load('cargas.integrado');
                }

                // ✅ Usa as cargas já carregadas para pegar os integrados
                $integrados = $this->cargas
                    ->pluck('integrado')
                    ->filter(
                        fn ($integrado) => $integrado !== null &&
                            ! empty($integrado->latitude) &&
                            ! empty($integrado->longitude)
                    )
                    ->unique('id') // Remove duplicados
                    ->values();

                if ($integrados->isEmpty()) {
                    return null;
                }

                $coords = $integrados->map(fn ($i) => "{$i->latitude},{$i->longitude}")->values();
                $waypoints = $coords->implode('|');

                $originEnc = urlencode($origin);
                $waypointsEnc = urlencode($waypoints);
                $destinationEnc = $originEnc; // volta ao ponto inicial

                $directionsUrl = "https://www.google.com/maps/dir/?api=1&origin={$originEnc}&waypoints={$waypointsEnc}&destination={$destinationEnc}&travelmode=driving";

                $items = $integrados->map(fn ($i) => [
                    'id' => $i->id,
                    'nome' => $i->nome ?? null,
                    'municipio' => $i->municipio ?? null,
                    'coords' => "{$i->latitude},{$i->longitude}",
                    'url' => "https://www.google.com/maps/dir/?api=1&origin={$originEnc}&destination=".urlencode("{$i->latitude},{$i->longitude}").'&travelmode=driving',
                ])->values()->toArray();

                return [
                    'directions_url' => $directionsUrl,
                    'items' => $items,
                ];
            }
        );
    }

    protected function documentosFreteResumo(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Evita N+1 no Filament
                if (! $this->relationLoaded('documentos')) {
                    return 'N/A';
                }

                $docs = $this->documentos
                    ->whereNotNull('viagem_id') // somente vinculados
                    ->map(fn ($doc) => [
                        'numero' => $doc->numero_documento,
                        'valor' => $doc->valor_liquido,
                    ])
                    ->values();

                if ($docs->isEmpty()) {
                    return 'Sem frete';
                }

                // Formato amigável para Table do Filament
                $result = $docs
                    ->map(fn ($d) => "Nº {$d['numero']} - R$".number_format($d['valor'], 2, ',', '.'))
                    ->implode('<br>');

                Log::debug('Documentos de frete para Viagem', [
                    'viagem_id' => $this->id,
                    'documentos' => $docs->toArray(),
                    'result' => $result,
                ]);

                return $result;
            }
        );
    }

    protected function parceiroFrete(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Evita N+1 no Filament
                if (! $this->relationLoaded('documentos')) {
                    return 'N/A';
                }

                $docs = $this->documentos
                    ->whereNotNull('viagem_id') // somente vinculados
                    ->map(fn ($doc) => [
                        'destino' => $doc->parceiro_destino,
                        'numero' => $doc->numero_documento,
                    ])
                    ->values();

                if ($docs->isEmpty()) {
                    return 'Sem frete';
                }

                // Formato amigável para Table do Filament
                return $docs
                    ->map(fn ($d) => "{$d['destino']}")
                    ->implode(';<br>');
            }
        );
    }

    protected function pendenciasResumo(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $pendencias = collect($this->pendencias ?? [])
                    ->filter(fn ($value) => filled($value))
                    ->values();

                if ($pendencias->isEmpty()) {
                    return $this->possui_pendencia
                        ? 'Pendencia sem detalhe sincronizado'
                        : 'Sem pendencias';
                }

                return $pendencias->implode('; ');
            }
        );
    }

    protected function integradosNomesView(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $integrados = collect($this->integrados_json ?? [])
                    ->map(fn ($integrado) => trim(($integrado['nome'] ?? '').' - '.($integrado['municipio'] ?? '')))
                    ->filter()
                    ->unique()
                    ->values();

                if ($integrados->isEmpty()) {
                    return 'Sem Carga Vinculada';
                }

                return $integrados->implode('<br>');
            }
        );
    }
}
