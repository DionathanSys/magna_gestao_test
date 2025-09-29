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
    <p>Solicitamos a emissão de CT-es para a placa {{{$payload->veiculo}}} referente as NF's em anexo.</p>

    <p>Valor total frete R$ {{number_format($payload->valorFreteTotal, 2, ',', '.') }}, sendo {{$payload->quantidadeCte}} CT-e(s), R$ {{number_format($payload->valorFreteUnitario, 2, ',', '.') }} cada CT-e. </p>

    <p>CNPJ Transportadora: 75.813.923/0010-52</p>

    <p>Motorista: {{$payload->motorista['nome']}}</p>

    <p>CPF: {{$payload->motorista['cpf']}}</p>

    <p>Início no município de Chapecó para PF: ICMS DIFERIDO, CST 051 ( campo de observações: ICMS DIFERIDO CFME ARTIGO 122, INCISO II, ANEXO 6, DO RICMS/SC)</p>

    <p>Favor responder este e-mail, incluindo todos os destinatários em cópia.</p>
    <p>Obrigado!</p>
    <p style='font-weight: bold; color: #000000;'>AxionSoft - Gestão</p>
</body>
</html>

