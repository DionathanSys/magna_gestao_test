<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Diário de Agendamentos</title>
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
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        
        .resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .resumo-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .resumo-card.pendente { border-left-color: #ffc107; }
        .resumo-card.execucao { border-left-color: #17a2b8; }
        .resumo-card.atrasado { border-left-color: #dc3545; }
        .resumo-card.concluido { border-left-color: #28a745; }
        
        .resumo-card h3 { margin: 0; font-size: 24px; color: #333; }
        .resumo-card p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
        
        .secao {
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        .secao-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        .secao-header.atrasados { background-color: #f8d7da; color: #721c24; }
        .secao-header.pendentes { background-color: #fff3cd; color: #856404; }
        .secao-header.execucao { background-color: #d1ecf1; color: #0c5460; }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .table th, .table td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #e9ecef;
        }
        .table th { 
            background-color: #f8f9fa; 
            font-weight: 600;
            color: #495057;
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status.pendente { background-color: #fff3cd; color: #856404; }
        .status.execucao { background-color: #d1ecf1; color: #0c5460; }
        .status.atrasado { background-color: #f8d7da; color: #721c24; }
        
        .prioridade {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .prioridade.alta { background-color: #f8d7da; color: #721c24; }
        .prioridade.media { background-color: #fff3cd; color: #856404; }
        .prioridade.baixa { background-color: #d4edda; color: #155724; }
        
        .vazio {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Relatório Diário de Agendamentos</h1>
            <p>{{ $dados['data_relatorio'] }} | Gerado em: {{ $dados['data_geracao'] }}</p>
        </div>

        <div class="content">
            <!-- Resumo -->
            <div class="resumo">
                <div class="resumo-card">
                    <h3>{{ $dados['resumo']['total_agendamentos_hoje'] }}</h3>
                    <p>Agendamentos Hoje</p>
                </div>
                <div class="resumo-card pendente">
                    <h3>{{ $dados['resumo']['total_pendentes'] }}</h3>
                    <p>Pendentes</p>
                </div>
                <div class="resumo-card pendente">
                    <h3>{{ $dados['resumo']['total_pendentes_sem_data'] }}</h3>
                    <p>Pendentes Sem Data</p>
                </div>
                <div class="resumo-card execucao">
                    <h3>{{ $dados['resumo']['total_em_execucao'] }}</h3>
                    <p>Em Execução</p>
                </div>
                <div class="resumo-card atrasado">
                    <h3>{{ $dados['resumo']['total_atrasados'] }}</h3>
                    <p>Atrasados</p>
                </div>
                <div class="resumo-card concluido">
                    <h3>{{ $dados['resumo']['total_concluidos_hoje'] }}</h3>
                    <p>Concluídos Hoje</p>
                </div>
                <div class="resumo-card">
                    <h3>{{ $dados['resumo']['veiculos_com_agendamento'] }}</h3>
                    <p>Veículos</p>
                </div>
            </div>

            <!-- Agendamentos Atrasados -->
            @if(count($dados['atrasados']) > 0)
                <div class="secao">
                    <div class="secao-header atrasados">
                        🚨 Agendamentos Atrasados ({{ count($dados['atrasados']) }})
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Veículo</th>
                                <th>Serviço</th>
                                <th>Plano Preventivo</th>
                                <th>Fornecedor Ext.</th>
                                <th>Atraso</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['atrasados'] as $agendamento)
                                <tr>
                                    <td>{{ $agendamento['data_agendamento'] }}</td>
                                    <td><strong>{{ $agendamento['veiculo_placa'] }}</strong></td>
                                    <td>{{ $agendamento['servico'] }}</td>
                                    <td>{{ $agendamento['plano_preventivo'] }}</td>
                                    <td>{{ $agendamento['parceiro'] }}</td>
                                    <td><span class="status atrasado">{{ abs($agendamento['dias_atraso']) }} dias</span></td>
                                    <td><span class="status {{ strtolower($agendamento['status']) }}">{{ $agendamento['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Agendamentos Pendentes Hoje -->
            @if(count($dados['pendentes']) > 0)
                <div class="secao">
                    <div class="secao-header pendentes">
                        ⏳ Agendamentos Pendentes Hoje ({{ count($dados['pendentes']) }})
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Serviço</th>
                                <th>Plano Preventivo</th>
                                <th>Fornecedor Ext.</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['pendentes'] as $agendamento)
                                <tr>
                                    <td>{{ $agendamento['veiculo_placa'] }}</td>
                                    <td>{{ $agendamento['servico'] }}</td>
                                    <td>{{ $agendamento['plano_preventivo'] }}</td>
                                    <td>{{ $agendamento['parceiro'] }}</td>
                                    <td><span class="status {{ strtolower($agendamento['status']) }}">{{ $agendamento['status'] }}</span></td>
                                    <td>{{ Str::limit($agendamento['observacoes'], 50) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Agendamentos para Amanhã -->
            @if(count($dados['amanha']) > 0)
                <div class="secao">
                    <div class="secao-header">
                        📅 Agendamentos para Amanhã ({{ count($dados['amanha']) }})
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Serviço</th>
                                <th>Plano Preventivo</th>
                                <th>Fornecedor Ext.</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['amanha'] as $agendamento)
                                <tr>
                                    <td>{{ $agendamento['veiculo_placa'] }}</td>
                                    <td>{{ $agendamento['servico'] }}</td>
                                    <td>{{ $agendamento['plano_preventivo'] }}</td>
                                    <td>{{ $agendamento['parceiro'] }}</td>
                                    <td><span class="status {{ strtolower($agendamento['status']) }}">{{ $agendamento['status'] }}</span></td>
                                    <td>{{ Str::limit($agendamento['observacoes'], 50) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Agendamentos Esta Semana -->
            @if(count($dados['esta_semana']) > 0)
                <div class="secao">
                    <div class="secao-header">
                        📆 Próximos da Semana ({{ count($dados['esta_semana']) }})
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Serviço</th>
                                <th>Plano Preventivo</th>
                                <th>Fornecedor Ext.</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['esta_semana'] as $agendamento)
                                <tr>
                                    <td>{{ $agendamento['veiculo_placa'] }}</td>
                                    <td>{{ $agendamento['servico'] }}</td>
                                    <td>{{ $agendamento['plano_preventivo'] }}</td>
                                    <td>{{ $agendamento['parceiro'] }}</td>
                                    <td><span class="status {{ strtolower($agendamento['status']) }}">{{ $agendamento['status'] }}</span></td>
                                    <td>{{ Str::limit($agendamento['observacoes'], 50) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

             <!-- Agendamentos Sem Data -->
            @if(count($dados['pendentes_sem_data']) > 0)
                <div class="secao">
                    <div class="secao-header">
                        📆 Sem Data ({{ count($dados['pendentes_sem_data']) }})
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Serviço</th>
                                <th>Plano Preventivo</th>
                                <th>Fornecedor Ext.</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dados['esta_semana'] as $agendamento)
                                <tr>
                                    <td>{{ $agendamento['veiculo_placa'] }}</td>
                                    <td>{{ $agendamento['servico'] }}</td>
                                    <td>{{ $agendamento['plano_preventivo'] }}</td>
                                    <td>{{ $agendamento['parceiro'] }}</td>
                                    <td><span class="status {{ strtolower($agendamento['status']) }}">{{ $agendamento['status'] }}</span></td>
                                    <td>{{ Str::limit($agendamento['observacoes'], 50) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Mensagem se não há agendamentos -->
            @if(count($dados['pendentes']) == 0 && count($dados['pendentes_sem_data']) == 0 && count($dados['em_execucao']) == 0 && count($dados['amanha']) == 0 && count($dados['esta_semana']) == 0 && count($dados['atrasados']) == 0)
                <div class="vazio">
                    <h3>✅ Tudo em dia!</h3>
                    <p>Não há agendamentos pendentes, em execução ou atrasados.</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>📧 Este é um email automático do sistema Magna Gestão.<br>
            Não responda este email.</p>
        </div>
    </div>
</body>
</html>