
Solicitamos a emissão de CT-es para a placa {{{$payload->placa}}} referente as NF's .<br>

Valor total do CT-e R$ {{number_format($payload->valor_total, 2, ',', '.') }} 1 CT-e<br>

CNPJ:{{$payload->transportadora['cnpj']}}<br>

RXM2C54<br>

{{$payload->motorista['nome']}} <br>

{{$payload->motorista['cpf']}} <br>


OBS: favor colocar frete como:1º CASO – Início no município de Água Doce para PF: ICMS DIFERIDO, CST 051 ( campo de observações: ICMS DIFERIDO CFME ARTIGO 122, INCISO II, ANEXO 6, DO RICMS/SC)<br>
{{ config('app.name') }}
