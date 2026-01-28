<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Ordens de Serviço - Matricial</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #000;
            line-height: 1.3;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .header h1 {
            font-size: 12px;
            margin-bottom: 3px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header p {
            font-size: 8px;
        }
        
        .ordem {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
            page-break-inside: avoid;
        }
        
        .ordem-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .linha {
            margin: 3px 0;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .value {
            display: inline-block;
        }
        
        .separator {
            border-top: 1px solid #000;
            margin: 8px 0;
        }
        
        .item-header {
            font-weight: bold;
            font-size: 10px;
            margin-top: 8px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .item {
            margin-left: 10px;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px dotted #000;
        }
        
        .comentario {
            margin-left: 15px;
            margin-top: 3px;
            padding: 3px;
            border-left: 2px solid #000;
            padding-left: 5px;
            font-size: 8px;
        }
        
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 15px;
            padding-top: 5px;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATORIO DE ORDENS DE SERVICO</h1>
        <p>{{ $dataGeracao }}</p>
        <p>Total: {{ $ordensServico->count() }} ordem(ns)</p>
    </div>

    @foreach($ordensServico as $ordem)
    <div class="ordem">
        <div class="ordem-title">
            ORDEM DE SERVICO #{{ $ordem->id }}
            @if($ordem->sankhyaId->isNotEmpty())
            - SANKHYA: {{ $ordem->sankhyaId->pluck('ordem_sankhya_id')->join(', ') }}
            @endif
        </div>
        
        <div class="linha">
            <span class="label">VEICULO:</span> {{ $ordem->veiculo?->placa ?? 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">DATA INICIO:</span> {{ $ordem->data_inicio ? \Carbon\Carbon::parse($ordem->data_inicio)->format('d/m/Y') : 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">QUILOMETRAGEM:</span> {{ number_format($ordem->quilometragem ?? 0, 0, ',', '.') }} KM
        </div>
        
        <div class="linha">
            <span class="label">TIPO MANUTENCAO:</span> {{ $ordem->tipo_manutencao?->value ?? 'N/A' }}
        </div>
        
        <div class="linha">
            <span class="label">STATUS:</span> {{ $ordem->status?->value ?? 'N/A' }}
        </div>
        
        @if($ordem->parceiro)
        <div class="linha">
            <span class="label">FORNECEDOR:</span> {{ $ordem->parceiro->nome }}
        </div>
        @endif
        
        <div class="linha">
            <span class="label">CRIADO EM:</span> {{ $ordem->created_at->format('d/m/Y H:i') }}
        </div>

        @if($ordem->itens->isNotEmpty())
        <div class="separator"></div>
        <div class="item-header">SERVICOS EXECUTADOS:</div>
        
        @foreach($ordem->itens as $item)
        <div class="item">
            <div><span class="label">ITEM #{{ $item->id }}</span> - {{ $item->servico?->descricao ?? 'N/A' }}</div>
            @if($item->posicao)
            <div><span class="label">POSICAO:</span> {{ $item->posicao }}</div>
            @endif
            @if($item->observacao)
            <div><span class="label">OBSERVACAO:</span> {{ $item->observacao }}</div>
            @endif
            <div><span class="label">STATUS:</span> {{ $item->status?->value ?? 'N/A' }}</div>
            
            @if($item->comentarios->isNotEmpty())
            @foreach($item->comentarios as $comentario)
            <div class="comentario">
                <strong>COMENTARIO - {{ $comentario->creator?->name ?? 'Sistema' }}</strong><br>
                {{ $comentario->created_at->format('d/m/Y H:i') }}<br>
                {{ $comentario->conteudo }}
            </div>
            @endforeach
            @endif
        </div>
        @endforeach
        @endif
    </div>
    @endforeach

    <div class="footer">
        ========== FIM DO RELATORIO ==========
    </div>
</body>
</html>
