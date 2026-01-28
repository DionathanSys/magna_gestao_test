<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Ordens de Serviço - Térmico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 8px;
            color: #000;
            line-height: 1.2;
            padding: 5px;
            width: 80mm;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        
        .header h1 {
            font-size: 11px;
            margin-bottom: 3px;
            font-weight: bold;
        }
        
        .header p {
            font-size: 7px;
        }
        
        .ordem {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000;
        }
        
        .ordem-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .linha {
            margin: 2px 0;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
        }
        
        .value {
            display: inline-block;
        }
        
        .separator {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        
        .item-header {
            font-weight: bold;
            font-size: 8px;
            margin-top: 5px;
            margin-bottom: 3px;
            text-decoration: underline;
        }
        
        .item {
            margin-left: 5px;
            margin-bottom: 3px;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .comentario {
            margin-left: 10px;
            margin-top: 2px;
            padding: 2px;
            background-color: #f0f0f0;
            font-size: 7px;
        }
        
        .footer {
            text-align: center;
            font-size: 7px;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ORDENS DE SERVICO</h1>
        <p>{{ $dataGeracao }}</p>
        <p>Total: {{ $ordensServico->count() }} ordem(ns)</p>
    </div>

    @foreach($ordensServico as $ordem)
    <div class="ordem">
        <div class="ordem-title">
            === OS #{{ $ordem->id }} ===
            @if($ordem->sankhyaId->isNotEmpty())
            <br>Sankhya: {{ $ordem->sankhyaId->pluck('ordem_sankhya_id')->join(', ') }}
            @endif
        </div>
        
        <div class="linha">
            <span class="label">Veiculo:</span> {{ $ordem->veiculo?->placa ?? 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">Data:</span> {{ $ordem->data_inicio ? \Carbon\Carbon::parse($ordem->data_inicio)->format('d/m/Y') : 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">KM:</span> {{ number_format($ordem->quilometragem ?? 0, 0, ',', '.') }}
        </div>
        
        <div class="linha">
            <span class="label">Tipo:</span> {{ $ordem->tipo_manutencao?->value ?? 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">Status:</span> {{ $ordem->status?->value ?? 'N/A' }}
        </div>
        
        @if($ordem->parceiro)
        <div class="linha">
            <span class="label">Fornecedor:</span> {{ $ordem->parceiro->nome }}
        </div>
        @endif
        
        <div class="linha">
            <span class="label">Criado:</span> {{ $ordem->created_at->format('d/m/Y H:i') }}
        </div>

        @if($ordem->itens->isNotEmpty())
        <div class="separator"></div>
        <div class="item-header">SERVICOS:</div>
        
        @foreach($ordem->itens as $item)
        <div class="item">
            <div><span class="label">#{{ $item->id }}</span> - {{ $item->servico?->descricao ?? 'N/A' }}</div>
            @if($item->posicao)
            <div><span class="label">Posicao:</span> {{ $item->posicao }}</div>
            @endif
            @if($item->observacao)
            <div><span class="label">Obs:</span> {{ $item->observacao }}</div>
            @endif
            <div><span class="label">Status:</span> {{ $item->status?->value ?? 'N/A' }}</div>
            
            @if($item->comentarios->isNotEmpty())
            @foreach($item->comentarios as $comentario)
            <div class="comentario">
                <strong>{{ $comentario->user?->name ?? 'Sistema' }}</strong><br>
                {{ $comentario->created_at->format('d/m/Y H:i') }}<br>
                {{ $comentario->comentario }}
            </div>
            @endforeach
            @endif
        </div>
        @endforeach
        @endif
    </div>
    @endforeach

    <div class="footer">
        === FIM DO RELATORIO ===
    </div>
</body>
</html>
