<x-mail::message>
# Dashboard de resultados

Olá, {{ $destinatarioNome }}.

Foi disponibilizado um dashboard com os resultados do período **{{ $periodo }}**.

{{ $quantidadeResultados }} resultado(s) de período estão incluído(s) no relatório.

<x-mail::button :url="$url">
Acessar dashboard
</x-mail::button>

Este link é individual e expira em **{{ $expiraEm }}**.

Se você não esperava receber este e-mail, desconsidere esta mensagem.

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>
