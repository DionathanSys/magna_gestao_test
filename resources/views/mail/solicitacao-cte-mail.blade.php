
<p>Solicitamos a emissão de CT-es para a placa {{{$payload->veiculo}}} referente as NF's em anexo.</p>

<p>Valor total frete R$ {{number_format($payload->valorFreteTotal, 2, ',', '.') }}, sendo {{$payload->quantidadeCte}} CT-e(s), R$ {{number_format($payload->valorFreteUnitario, 2, ',', '.') }} cada CT-e. </p>

<p>CNPJ Transportadora: 75.813.923/0010-52</p>

<p>Motorista: {{$payload->motorista['nome']}}</p>

<p>CPF: {{$payload->motorista['cpf']}}</p>

<p>Favor responder este e-mail, incluindo todos os destinatários em cópia.</p>
<p>Obrigado!</p>
<p style='font-weight: bold'>AxionSoft - Gestão</p>

