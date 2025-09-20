<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use Filament\Actions\Action;
use App\Models;
use App\Services;
use App\Enum;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VincularServicoOrdemServicoAction
{
    public static function make($ordemServicoId = null): Action
    {
        return Action::make('vincular_servico')
            ->label('Adicionar Serviço')
            ->icon('heroicon-o-plus')
            ->schema(fn(Schema $schema) => ItemOrdemServicoForm::configure($schema))
            ->model(Models\ItemOrdemServico::class)
            ->mutateDataUsing(function (array $data) use ($ordemServicoId): array {
                $data['ordem_servico_id'] = $ordemServicoId;
                return $data;
            })
            ->action(function (array $data, string $model, Action $action): ?Model {
                ds($data);
                $service = new Services\ItemOrdemServico\ItemOrdemServicoService();
                $itemOrdemServico = $service->create($data);

                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->halt();
                    return null;
                }

                notify::success(mensagem: 'Serviço vinculado com sucesso!');
                return $itemOrdemServico;
            });
    }


}
