<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            color: #000000;
            font-family: Arial, sans-serif;
        }
        p {
            color: #000000;
        }
    </style>
</head>
<body>
    <p>Solicitamos a emissão de CT-e para a placa {{{$payload->veiculo}}} referente as NF's ({{is_array($payload->nro_notas) ? implode(', ', $payload->nro_notas) : $payload->nro_notas}}) em anexo.</p>

     @if ($payload->cte_retroativo)
        <p>CTe Retroativo</p>
    @endif 

    @if ($payload->cte_complementar)
        <p>Complementar ao CT-e: {{$payload->cte_referencia}}</p>
    @endif

    <p>Valor total frete R$ {{number_format($payload->valorFreteTotal, 2, ',', '.') }}, sendo {{$payload->quantidadeCte}} CT-e(s), R$ {{number_format($payload->valorFreteUnitario, 2, ',', '.') }} cada CT-e. </p>

    <p>CNPJ Transportadora: 75.813.923/0001-61</p>

    <p>Motorista: {{$payload->motorista['nome']}}</p>

    <p>CPF: {{$payload->motorista['cpf']}}</p>

    <h4>Observações:</h4>
    <p>Início no município de Chapecó para PF: ICMS DIFERIDO, CST 051 (campo de observações: ICMS DIFERIDO CFME ARTIGO 122, INCISO II, ANEXO 6, DO RICMS/SC)</p>
    <p>Remetente: Bugio Nutrição – 50.593.076/0001-46</p>
    @foreach ($payload->destinos as $key => $destino)
        <p>Destinatário: {{ $destino['integrado_nome'] }}</p>
    @endforeach
    <p>Tomador: Bugio Agropecuária – 82.996.521/0001-05</p>

    <p>Favor responder este e-mail, incluindo todos os destinatários em cópia.</p>
    <p>Obrigado!</p>
    <p style='font-weight: bold; color: #000000;'>AxionSoft - Gestão</p>
</body>
</html>

