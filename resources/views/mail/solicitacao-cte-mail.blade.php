
Solicitamos a emissão de CT-es para a placa {{{$payload->veiculo}}} referente as NF's em anexo.<br>

Valor total frete R$ {{number_format($payload->valorFreteTotal, 2, ',', '.') }}, sendo {{$payload->quantidadeCte}} CT-e(s), R$ {{number_format($payload->valorFreteUnitario, 2, ',', '.') }} cada CT-e <br>

CNPJ:75.813.923/0010-52<br>

Motorista: {{$payload->motorista['nome']}} <br><br>

CPF: {{$payload->motorista['cpf']}} <br><br>

{{ config('app.name') }}
