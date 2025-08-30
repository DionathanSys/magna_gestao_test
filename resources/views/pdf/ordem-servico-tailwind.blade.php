<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS. #{{ $ordemServico->id }}</title>
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    <style>
        @page {
            margin: 1cm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }

        .status-pendente {
            @apply bg-yellow-100 text-yellow-800;
        }

        .status-em-andamento {
            @apply bg-blue-100 text-blue-800;
        }

        .status-concluido {
            @apply bg-green-100 text-green-800;
        }

        .status-cancelado {
            @apply bg-red-100 text-red-800;
        }
    </style>
</head>
<body class="bg-red-500 text-gray-900 text-sm leading-relaxed">
    <!-- Header -->
    <div class="text-center mb-6 pb-3 border-b-2 border-blue-500">
        <h1 class="text-2xl font-bold text-blue-600 mb-2">ORDEM DE SERVIÇO #{{ $ordemServico->id }}</h1>
        <p class="text-gray-600 text-xs">Sistema de Gestão de Frota - Relatório gerado em {{ $dataGeracao }}</p>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 gap-4 mb-6 bg-red-500">
        <!-- Informações da OS -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 mb-3 text-sm">Informações da Ordem</h3>

            <div class="space-y-3">
                <div>
                    <span class="text-xs font-medium text-gray-600 block">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-{{ str_replace(' ', '-', strtolower($ordemServico->status->value)) }}">
                        {{ $ordemServico->status }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-medium text-gray-600 block">Tipo Manutenção:</span>
                    <span class="text-sm text-gray-900">{{ $ordemServico->tipo_manutencao }}</span>
                </div>

                <div>
                    <span class="text-xs font-medium text-gray-600 block">Data Abertura:</span>
                    <span class="text-sm text-gray-900">{{ date('d/m/Y H:i', strtotime($ordemServico->data_inicio)) }}</span>
                </div>

                @if($ordemServico->data_fim)
                <div>
                    <span class="text-xs font-medium text-gray-600 block">Data Encerramento:</span>
                    <span class="text-sm text-gray-900">{{ date('d/m/Y H:i', strtotime($ordemServico->data_fim)) }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Informações do Veículo -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-700 mb-3 text-sm">Informações do Veículo</h3>

            <div class="grid grid-cols-2 gap-3">
                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Veículo</div>
                    <div class="font-bold text-gray-900">{{ e($ordemServico->veiculo->placa ?? 'N/A') }}</div>
                </div>

                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Modelo</div>
                    <div class="font-bold text-gray-900">{{ e($ordemServico->veiculo->modelo ?? 'N/A') }}</div>
                </div>

                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Quilometragem OS</div>
                    <div class="font-bold text-blue-600">{{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }} km</div>
                </div>

                @if($ordemServico->parceiro)
                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Fornecedor</div>
                    <div class="font-bold text-gray-900">{{ e($ordemServico->parceiro->nome) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Serviços Executados -->
    <div class="mb-8">
        <div class="bg-gray-700 text-white px-4 py-3 rounded-t-lg">
            <h2 class="text-sm font-bold">Serviços Executados</h2>
        </div>

        @if($ordemServico->itens && $ordemServico->itens->count() > 0)
        <div class="bg-white border border-gray-200 rounded-b-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                            Código
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Descrição do Serviço
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                            Posição
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Observação
                        </th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ordemServico->itens as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-center font-bold text-gray-900">
                            {{ e($item->servico->id ?? 'N/A') }}
                        </td>
                        <td class="px-3 py-2 text-gray-900">
                            {{ e($item->servico->descricao ?? 'N/A') }}
                        </td>
                        <td class="px-3 py-2 text-center text-gray-900">
                            {{ e($item->posicao ?? 'N/A') }}
                        </td>
                        <td class="px-3 py-2 text-gray-700">
                            {{ e($item->observacao ?? 'Sem observações') }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-{{ str_replace(' ', '-', strtolower($item->status->value)) }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white border border-gray-200 rounded-b-lg p-8 text-center">
            <div class="text-gray-500 italic">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>Nenhum serviço cadastrado para esta ordem de serviço.</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Planos Preventivos (se existirem) -->
    @if($ordemServico->planoPreventivoVinculado && $ordemServico->planoPreventivoVinculado->count() > 0)
    <div class="mb-8">
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-t-lg">
            <h2 class="text-sm font-bold">Planos Preventivos Vinculados</h2>
        </div>

        <div class="bg-white border border-gray-200 rounded-b-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                            ID Plano
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Descrição
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ordemServico->planoPreventivoVinculado as $planoVinculado)
                    @if($planoVinculado->planoPreventivo)
                    <tr>
                        <td class="px-3 py-2 text-center font-bold text-indigo-600">
                            {{ $planoVinculado->planoPreventivo->id ?? 'N/A' }}
                        </td>
                        <td class="px-3 py-2 text-gray-900">
                            {{ e($planoVinculado->planoPreventivo->descricao ?? 'N/A') }}
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-center text-gray-500 italic">
                            Plano preventivo não encontrado
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Informações Adicionais -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Informações Adicionais</h4>
                <div class="text-sm text-blue-700 space-y-1">
                    <p><span class="font-medium">Data de Criação:</span> {{ date('d/m/Y H:i:s', strtotime($ordemServico->created_at)) }}</p>
                    @if($ordemServico->updated_at != $ordemServico->created_at)
                    <p><span class="font-medium">Última Atualização:</span> {{ date('d/m/Y H:i:s', strtotime($ordemServico->updated_at)) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="fixed bottom-4 left-0 right-0 text-center">
        <div class="bg-white border-t border-gray-200 pt-2">
            <p class="text-xs text-gray-500">
                Sistema de Gestão de Frota - Ordem de Serviço #{{ $ordemServico->id }} - Gerado automaticamente em {{ date('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>
