<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models\CargaViagem;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\Integrado\IntegradoDestinoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class VincularDestinoRecebidoAction
{
    public static function make(): Action
    {
        return Action::make('vincular-destino-recebido')
            ->label('Vincular destino recebido')
            ->icon('heroicon-o-link')
            ->color('warning')
            ->visible(fn (Viagem $record): bool => self::cargasPendentes($record)->isNotEmpty())
            ->schema([
                Placeholder::make('destino_recebido')
                    ->label('Destino recebido da integração')
                    ->content(fn (Viagem $record): HtmlString => new HtmlString(
                        self::cargasPendentes($record)
                            ->pluck('destino_externo')
                            ->map(fn (?string $destino): string => e($destino ?: 'Não informado'))
                            ->implode('<br>')
                    )),
                Select::make('carga_id')
                    ->label('Destino a vincular')
                    ->options(fn (Viagem $record): array => self::cargasPendentes($record)
                        ->mapWithKeys(fn (CargaViagem $carga): array => [$carga->id => $carga->destino_externo ?: 'Não informado'])
                        ->all())
                    ->default(fn (Viagem $record): ?int => self::cargasPendentes($record)->first()?->id)
                    ->visible(fn (Viagem $record): bool => self::cargasPendentes($record)->count() > 1)
                    ->required(),
                Select::make('integrado_id')
                    ->label('Integrado correto')
                    ->getSearchResultsUsing(fn (string $search): array => Integrado::query()
                        ->where(function ($query) use ($search): void {
                            $query
                                ->where('codigo', 'like', "%{$search}%")
                                ->orWhere('nome', 'like', "%{$search}%");
                        })
                        ->orderBy('nome')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Integrado $integrado): array => [
                            $integrado->id => trim("{$integrado->codigo} {$integrado->nome}"),
                        ])
                        ->all())
                    ->getOptionLabelUsing(fn (string $value): ?string => Integrado::query()
                        ->whereKey($value)
                        ->get()
                        ->map(fn (Integrado $integrado): string => trim("{$integrado->codigo} {$integrado->nome}"))
                        ->first())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Viagem $record, array $data): void {
                $cargasPendentes = self::cargasPendentes($record);
                $cargaId = $data['carga_id'] ?? $cargasPendentes->first()?->id;
                $carga = $cargasPendentes->firstWhere('id', $cargaId);

                if (! $carga) {
                    Notification::make()
                        ->danger()
                        ->title('Destino não encontrado')
                        ->send();

                    return;
                }

                $integrado = Integrado::query()->findOrFail($data['integrado_id']);
                $carga->update([
                    'integrado_id' => $integrado->id,
                    'updated_by' => Auth::id(),
                ]);

                $aliasRegistrado = app(IntegradoDestinoService::class)
                    ->registrarAlias($integrado, $carga->destino_externo);

                Notification::make()
                    ->success()
                    ->title('Destino vinculado')
                    ->body($aliasRegistrado
                        ? 'O nome recebido foi salvo para vínculos automáticos futuros.'
                        : 'O destino foi vinculado, mas o nome já é um alias de outro integrado.')
                    ->send();
            });
    }

    private static function cargasPendentes(Viagem $viagem)
    {
        return $viagem->cargas
            ->whereNull('integrado_id')
            ->filter(fn (CargaViagem $carga): bool => filled($carga->destino_externo))
            ->values();
    }
}
