
Solicitamos a emissão de CT-es para a placa {{{$payload->veiculo}}} referente as NF's .<br>

Valor total frete R$ {{number_format($payload->valorFreteTotal, 2, ',', '.') }}, sendo {{$payload->quantidadeCte}} CT-e(s), R$ {{number_format($payload->valorFreteUnitario, 2, ',', '.') }} cadaa CT-e <br>

CNPJ:75.813.923/0010-52<br>

{{$payload->motorista['nome']}} <br>

{{$payload->motorista['cpf']}} <br>

{{ config('app.name') }}
