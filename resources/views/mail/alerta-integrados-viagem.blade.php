<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alerta - Integrados com Viagens</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            line-height: 1.6; 
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        
        .resumo-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .resumo-table td {
            width: 33.33%;
            padding: 10px;
            vertical-align: top;
        }
        .resumo-card {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #f59e0b;
        }
        .resumo-card h3 { margin: 0; font-size: 32px; color: #b45309; }
        .resumo-card p { margin: 5px 0 0 0; color: #92400e; font-size: 14px; }
        
        .integrado-section {
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #f59e0b;
            background: #fffbeb;
        }
        .integrado-header {
            background-color: #fef3c7;
            padding: 15px 20px;
            border-bottom: 2px solid #f59e0b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .integrado-header h3 {
            margin: 0;
            color: #92400e;
            font-size: 18px;
        }
        .integrado-header .badge {
            background: #f59e0b;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .integrado-info {
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #fde68a;
            font-size: 14px;
            color: #78716c;
        }
        .integrado-info strong {
            color: #92400e;
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse;
            background: white;
        }
        .table th, .table td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #fde68a;
            font-size: 13px;
        }
        .table th { 
            background-color: #fef3c7; 
            font-weight: 600;
            color: #92400e;
        }
        .table tr:hover {
            background-color: #fffbeb;
        }
        .table td {
            color: #57534e;
        }
        
        .vazio {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        .footer {
            background-color: #fef3c7;
            padding: 20px;
            text-align: center;
            color: #92400e;
            font-size: 14px;
            border-top: 2px solid #f59e0b;
        }
        
        .km-dispersao {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .km-dispersao.alto {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .km-dispersao.medio {
            background-color: #fef3c7;
            color: #92400e;
        }
        .km-dispersao.baixo {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Alerta - Integrados com Viagens</h1>
            <p>Gerado em: {{ $dados['data_processamento'] }}</p>
        </div>

        <div class="content">
            <!-- Resumo -->
            <table class="resumo-table">
                <tr>
                    <td>
                        <div class="resumo-card">
                            <h3>{{ $dados['total_integrados'] }}</h3>
                            <p>Integrados com Alerta</p>
                        </div>
                    </td>
                    <td>
                        <div class="resumo-card">
                            <h3>{{ $dados['total_viagens'] }}</h3>
                            <p>Total de Viagens</p>
                        </div>
                    </td>
                    <td>
                        <div class="resumo-card">
                            <h3>{{ number_format($dados['total_viagens'] / max($dados['total_integrados'], 1), 1) }}</h3>
                            <p>Viagens por Integrado</p>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Lista de Integrados -->
            @foreach($dados['integrados'] as $integrado)
                <div class="integrado-section">
                    <div class="integrado-header">
                        <h3>
                            {{ $integrado['integrado']['codigo'] }} - {{ $integrado['integrado']['nome'] }}
                        </h3>
                        <span class="badge">{{ $integrado['total_viagens'] }} viagens</span>
                    </div>
                    
                    <div class="integrado-info">
                        <strong>Município:</strong> {{ $integrado['integrado']['municipio'] ?? 'N/A' }}
                        &nbsp;|&nbsp;
                        <strong>Cliente:</strong> {{ $integrado['integrado']['cliente'] ?? 'N/A' }}
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nº Viagem</th>
                                <th>Doc. Transp.</th>
                                <th>Veículo</th>
                                <th>Data Comp.</th>
                                <th>Km Rodado</th>
                                <th>Km Dispersão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($integrado['viagens'] as $viagem)
                                <tr>
                                    <td><strong>{{ $viagem['numero_viagem'] }}</strong></td>
                                    <td>{{ $viagem['documento_transporte'] ?? 'N/A' }}</td>
                                    <td>{{ $viagem['veiculo_placa'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($viagem['data_competencia'])->format('d/m/Y') }}</td>
                                    <td>{{ number_format($viagem['km_rodado'], 2, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $kmDisp = abs($viagem['km_dispersao']);
                                            $classe = $kmDisp > 5 ? 'alto' : ($kmDisp > 2 ? 'medio' : 'baixo');
                                        @endphp
                                        <span class="km-dispersao {{ $classe }}">
                                            {{ number_format($viagem['km_dispersao'], 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            @if(count($dados['integrados']) == 0)
                <div class="vazio">
                    <h3>✅ Nenhum alerta</h3>
                    <p>Não há viagens para integrados com alerta configurado.</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>⚠️ Este é um email automático de alerta do sistema Magna Gestão.<br>
            Não responda este email.</p>
        </div>
    </div>
</body>
</html>