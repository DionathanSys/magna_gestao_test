<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Ordens de Serviço</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .header p {
            font-size: 9px;
            color: #7f8c8d;
        }
        
        .ordem-servico {
            margin-bottom: 25px;
            page-break-inside: avoid;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
        }
        
        .ordem-header {
            background-color: #3498db;
            color: white;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 3px 5px;
            width: 25%;
            background-color: #ecf0f1;
            border: 1px solid #ddd;
        }
        
        .info-value {
            display: table-cell;
            padding: 3px 5px;
            width: 25%;
            border: 1px solid #ddd;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #2c3e50;
            padding: 5px;
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table th {
            background-color: #34495e;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #27ae60;
            color: white;
        }
        
        .badge-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .badge-info {
            background-color: #3498db;
            color: white;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-data {
            text-align: center;
            padding: 10px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Ordens de Serviço</h1>
        <p>Gerado em: {{ $dataGeracao }}</p>
        <p>Total de Ordens: {{ $ordensServico->count() }}</p>
    </div>

    @foreach($ordensServico as $ordem)
    <div class="ordem-servico">
        <div class="ordem-header">
            OS #{{ $ordem->id }}
            @if($ordem->sankhyaId->isNotEmpty())
                - Sankhya: {{ $ordem->sankhyaId->pluck('ordem_sankhya_id')->join(', ') }}
            @endif
        </div>

        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Veículo:</div>
                <div class="info-value">{{ $ordem->veiculo?->placa ?? 'N/A' }}</div>
                <div class="info-label">Data Início:</div>
                <div class="info-value">{{ $ordem->data_inicio ? \Carbon\Carbon::parse($ordem->data_inicio)->format('d/m/Y') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Quilometragem:</div>
                <div class="info-value">{{ number_format($ordem->quilometragem ?? 0, 0, ',', '.') }} km</div>
                <div class="info-label">Tipo Manutenção:</div>
                <div class="info-value">{{ $ordem->tipo_manutencao?->value ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="badge badge-{{ $ordem->status?->value === 'Aberto' ? 'info' : ($ordem->status?->value === 'Concluído' ? 'success' : 'warning') }}">
                        {{ $ordem->status?->value ?? 'N/A' }}
                    </span>
                </div>
                <div class="info-label">Fornecedor:</div>
                <div class="info-value">{{ $ordem->parceiro?->nome ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Criado em:</div>
                <div class="info-value" colspan="3">{{ $ordem->created_at->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>

        @if($ordem->itens->isNotEmpty())
        <div class="section-title">Itens da Ordem de Serviço</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 35%;">Serviço</th>
                    <th style="width: 12%;">Posição</th>
                    <th style="width: 30%;">Observação</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordem->itens as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->servico?->descricao ?? 'N/A' }}</td>
                    <td>{{ $item->posicao ?? 'N/A' }}</td>
                    <td>{{ $item->observacao ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $item->status?->value === 'Aberto' ? 'info' : ($item->status?->value === 'Concluído' ? 'success' : 'warning') }}">
                            {{ $item->status?->value ?? 'N/A' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="section-title">Itens da Ordem de Serviço</div>
        <div class="no-data">Nenhum item cadastrado</div>
        @endif

        @if($ordem->sankhyaId->isNotEmpty())
        <div class="section-title">Ordens Sankhya Vinculadas</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">ID</th>
                    <th style="width: 80%;">Ordem Sankhya ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordem->sankhyaId as $sankhya)
                <tr>
                    <td>{{ $sankhya->id }}</td>
                    <td>{{ $sankhya->ordem_sankhya_id }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if(!$loop->last)
    <div style="margin-bottom: 15px;"></div>
    @endif
    @endforeach

    <div class="footer">
        Página {PAGENO} de {nb}
    </div>
</body>
</html>
