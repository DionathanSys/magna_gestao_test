<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #{{ $ordemServico->id }}</title>
    <style>
        body {
            font-family: "Courier New", monospace;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
        }

        .box {
            border: 1px solid #000;
            padding: 4px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .no-border td {
            border: none;
        }

        .linha {
            border-bottom: 1px dashed #000;
            height: 14px;
        }
    </style>

</head>

<body>
    <div class="box">
        <table class="no-border">
            <tr>
                <td><b>ORDEM DE SERVIÇO:</b> {{ $ordemServico->id }}</td>
                <td><b>Data:</b> {{ date('d/m/Y', strtotime($ordemServico->data_inicio)) }}</td>
                <td><b>Status:</b> {{ $ordemServico->status }}</td>
            </tr>
            <tr>
                <td><b>Veículo:</b> {{ $ordemServico->veiculo->placa ?? '---' }}</td>
                <td><b>Modelo:</b> {{ $ordemServico->veiculo->modelo ?? '---' }}</td>
                <td><b>KM:</b> {{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table class="no-border">
            <tr>
                <td width="25%"><b>Status:</b></td>
                <td width="25%">{{ $ordemServico->status }}</td>

                <td width="25%"><b>Tipo Manut.:</b></td>
                <td width="25%">{{ $ordemServico->tipo_manutencao }}</td>
            </tr>

            <tr>
                <td><b>Data Abertura:</b></td>
                <td>{{ date('d/m/Y H:i', strtotime($ordemServico->data_inicio)) }}</td>

                <td><b>Data Encerr.:</b></td>
                <td>
                    {{ $ordemServico->data_fim ? date('d/m/Y H:i', strtotime($ordemServico->data_fim)) : '________________' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table class="no-border">
            <tr>
                <td width="20%"><b>Veículo:</b></td>
                <td width="30%">{{ $ordemServico->veiculo->placa ?? '_____' }}</td>

                <td width="20%"><b>Modelo:</b></td>
                <td width="30%">{{ $ordemServico->veiculo->modelo ?? '_____' }}</td>
            </tr>

            <tr>
                <td><b>KM na OS:</b></td>
                <td>{{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }}</td>

                <td><b>Fornecedor:</b></td>
                <td>
                    {{ $ordemServico->parceiro->nome ?? '________________________' }}
                </td>
            </tr>
        </table>
    </div>


    <div class="clearfix"></div>

    <div class="box">
        <b>SERVIÇOS EXECUTADOS</b>

        <table>
            <thead>
                <tr>
                    <th width="10%">ID</th>
                    <th width="45%">Descrição</th>
                    <th width="10%">Pos.</th>
                    <th width="35%">Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ordemServico->itens as $item)
                    <tr>
                        <td class="center">{{ $item->id }}</td>
                        <td>{{ $item->servico->descricao }}</td>
                        <td class="center">{{ $item->posicao }}</td>
                        <td>{{ $item->observacao }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>




    <div class="box">
        <b>APONTAMENTO DE HORAS</b>

        <table>
            <thead>
                <tr>
                    <th width="45%">Mecânico</th>
                    <th width="15%">Início</th>
                    <th width="15%">Fim</th>
                    <th width="25%">Assinatura</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
                <tr>
                    <td>________________________</td>
                    <td>____:____</td>
                    <td>____:____</td>
                    <td>________________________</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="box">
        <b>OBSERVAÇÕES GERAIS</b>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
        <div class="linha"></div>
    </div>
    <div class="footer">
        <p>Sistema de Gestão de Frota - Ordem de Serviço #{{ $ordemServico->id }} - Gerado automaticamente em
            {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <div class="footer">
        @if ($osSankhya->isNotEmpty())
            <p>
                Associado às ordens
                {{ $osSankhya->pluck('ordem_sankhya_id')->implode(', ') }}
                do sistema Sankhya
            </p>
        @endif
    </div>
</body>

</html>
