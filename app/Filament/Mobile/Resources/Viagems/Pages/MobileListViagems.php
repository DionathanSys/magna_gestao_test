<?php

namespace App\Filament\Mobile\Resources\Viagems\Pages;

use App\Filament\Mobile\Resources\Viagems\ViagemResource;
use App\Models\Viagem;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class MobileListViagems extends Page
{
    use WithPagination;

    protected static string $resource = ViagemResource::class;

    protected static ?string $title = 'Viagens';

    protected string $view = 'filament.mobile.resources.viagems.pages.mobile-list-viagems';

    public string $activeTab = 'recentes';

    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function getViagensProperty(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->latest('data_inicio')
            ->latest('id')
            ->paginate(12);
    }

    public function getRecentesCount(): int
    {
        return Viagem::query()->count();
    }

    public function getHojeCount(): int
    {
        return Viagem::query()->whereDate('data_inicio', today())->count();
    }

    public function getPendenciasCount(): int
    {
        return Viagem::query()->where('possui_pendencia', true)->count();
    }

    public function getNaoConferidasCount(): int
    {
        return Viagem::query()->where('conferido', false)->count();
    }

    public function getSemDocumentoCount(): int
    {
        return Viagem::query()->whereNull('documento_transporte')->count();
    }

    public function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return 'Sem data';
        }

        return Carbon::parse($value)->format('d/m H:i');
    }

    public function formatNumber(float|int|null $value, int $decimals = 0): string
    {
        return number_format((float) ($value ?? 0), $decimals, ',', '.');
    }

    public function getDispersaoColor(Viagem $viagem): string
    {
        return ((float) ($viagem->km_dispersao ?? 0)) > 3.99 ? 'danger' : 'info';
    }

    public function getConferenciaColor(Viagem $viagem): string
    {
        if ($viagem->ignorar) {
            return 'gray';
        }

        if ($viagem->possui_pendencia) {
            return 'danger';
        }

        return $viagem->conferido ? 'success' : 'warning';
    }

    public function getConferenciaLabel(Viagem $viagem): string
    {
        if ($viagem->ignorar) {
            return 'Ignorada';
        }

        if ($viagem->possui_pendencia) {
            return 'Pendência';
        }

        return $viagem->conferido ? 'Conferida' : 'Não conferida';
    }

    public function getIntegradosResumo(Viagem $viagem): string
    {
        $integrados = collect($viagem->integrados_json ?? [])
            ->map(fn (array $integrado): string => trim(($integrado['nome'] ?? '').' - '.($integrado['municipio'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($integrados->isEmpty()) {
            $integrados = $viagem->cargas
                ->pluck('integrado')
                ->filter()
                ->map(fn ($integrado): string => trim(($integrado->nome ?? '').' - '.($integrado->municipio ?? '')))
                ->filter()
                ->unique()
                ->values();
        }

        return $integrados->take(2)->implode(' / ') ?: 'Sem integrado';
    }

    protected function baseQuery(): Builder
    {
        return Viagem::query()
            ->with([
                'veiculo:id,placa',
                'cargas.integrado:id,codigo,nome,municipio',
            ])
            ->withCount(['cargas', 'documentos'])
            ->when($this->activeTab === 'hoje', fn (Builder $query): Builder => $query->whereDate('data_inicio', today()))
            ->when($this->activeTab === 'pendencias', fn (Builder $query): Builder => $query->where('possui_pendencia', true))
            ->when($this->activeTab === 'nao-conferidas', fn (Builder $query): Builder => $query->where('conferido', false))
            ->when($this->activeTab === 'sem-documento', fn (Builder $query): Builder => $query->whereNull('documento_transporte'))
            ->when(filled($this->busca), function (Builder $query): void {
                $busca = trim($this->busca);

                $query->where(function (Builder $query) use ($busca): void {
                    $query
                        ->where('numero_viagem', 'like', "%{$busca}%")
                        ->orWhere('numero_interno', 'like', "%{$busca}%")
                        ->orWhere('documento_transporte', 'like', "%{$busca}%")
                        ->orWhereHas('veiculo', fn (Builder $query): Builder => $query->where('placa', 'like', "%{$busca}%"))
                        ->orWhereHas('cargas.integrado', function (Builder $query) use ($busca): void {
                            $query
                                ->where('codigo', 'like', "%{$busca}%")
                                ->orWhere('nome', 'like', "%{$busca}%")
                                ->orWhere('municipio', 'like', "%{$busca}%");
                        });
                });
            });
    }
}
