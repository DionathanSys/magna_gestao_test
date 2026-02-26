<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Documentos de Frete</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 8px;
        }

        .header h1 {
            color: #0066cc;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .header p {
            color: #666;
            font-size: 9px;
        }

        .veiculo-header {
            background-color: #e8f0fe;
            border: 1px solid #0066cc;
            border-radius: 3px;
            padding: 8px 10px;
            margin-bottom: 8px;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .veiculo-header .placa {
            font-size: 13px;
            font-weight: bold;
            color: #0066cc;
        }

        .veiculo-info {
            display: inline;
            font-size: 10px;
            color: #444;
        }

        .veiculo-info span {
            margin-right: 20px;
        }

        .veiculo-info .label {
            font-weight: bold;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #0066cc;
            color: white;
            padding: 5px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #0055aa;
        }

        td {
            padding: 4px;
            border: 1px solid #ddd;
            font-size: 8px;
            word-wrap: break-word;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            background-color: #e8f0fe !important;
            font-weight: bold;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        .sem-dados {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>RELATÓRIO DE DOCUMENTOS DE FRETE</h1>
    <p>Gerado em: {{ $dataGeracao }}</p>
</div>

@if(count($veiculos) > 0)
    @foreach($veiculos as $index => $veiculo)
        @if($index > 0)
            <div class="page-break"></div>
            <div class="header">
                <h1>RELATÓRIO DE DOCUMENTOS DE FRETE</h1>
                <p>Gerado em: {{ $dataGeracao }}</p>
            </div>
        @endif

        <div class="veiculo-header">
            <span class="placa">{{ $veiculo['placa'] }}</span>
            &nbsp;&nbsp;&nbsp;
            <span class="veiculo-info">
                <span><span class="label">Valor Total Frete:</span> R$ {{ number_format($veiculo['total_frete'], 2, ',', '.') }}</span>
                <span><span class="label">Qtde de Documentos:</span> {{ $veiculo['qtde_documentos'] }}</span>
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">ID</th>
                    <th style="width: 9%;" class="text-center">Nº Documento</th>
                    <th style="width: 9%;" class="text-center">Nº Sequencial</th>
                    <th style="width: 12%;">Nro Notas</th>
                    <th style="width: 9%;" class="text-center">Data Competência</th>
                    <th style="width: {{ $exibirVinculos ? '30%' : '40%' }};">Destino</th>
                    <th style="width: 9%;" class="text-right">Frete</th>
                    @if($exibirVinculos)
                        <th style="width: 9%;" class="text-center">Doc. Frete ID</th>
                        <th style="width: 9%;" class="text-center">Viagem ID</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($veiculo['registros'] as $registro)
                    <tr>
                        <td class="text-center">{{ $registro['id'] }}</td>
                        <td class="text-center">{{ $registro['nro_documento'] ?? '-' }}</td>
                        <td class="text-center">{{ $registro['numero_sequencial'] ? str_pad($registro['numero_sequencial'], 6, '0', STR_PAD_LEFT) : '-' }}</td>
                        <td>{{ $registro['nro_notas_formatado'] }}</td>
                        <td class="text-center">{{ $registro['data_competencia'] ? \Carbon\Carbon::parse($registro['data_competencia'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ $registro['destino_formatado'] }}</td>
                        <td class="text-right">R$ {{ number_format($registro['frete'] ?? 0, 2, ',', '.') }}</td>
                        @if($exibirVinculos)
                            <td class="text-center">{{ $registro['documento_frete_id'] ?? '-' }}</td>
                            <td class="text-center">{{ $registro['viagem_id'] ?? '-' }}</td>
                        @endif
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="{{ $exibirVinculos ? 6 : 6 }}" class="text-right">Total Veículo:</td>
                    <td class="text-right">R$ {{ number_format($veiculo['total_frete'], 2, ',', '.') }}</td>
                    @if($exibirVinculos)
                        <td colspan="2"></td>
                    @endif
                </tr>
            </tbody>
        </table>
    @endforeach
@else
    <div class="sem-dados">
        <p><strong>Nenhum registro encontrado.</strong></p>
    </div>
@endif

<div class="footer">
    <p>Sistema de Gestão - Magna | Relatório gerado automaticamente</p>
</div>
</body>
</html>
