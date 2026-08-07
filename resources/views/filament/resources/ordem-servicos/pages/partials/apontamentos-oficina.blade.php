<section class="os-list-panel">
    <div class="os-list-title">Apontamentos da Oficina</div>

    <div class="os-simple-list" style="margin-top: 0.75rem;">
        <div class="os-simple-item">
            <strong>Trabalhando agora</strong>

            @if ($record->apontamentosAbertosOficina->isEmpty())
                <span>Nenhum apontamento em aberto.</span>
            @else
                @foreach ($record->apontamentosAbertosOficina->sortBy('iniciado_em') as $apontamento)
                    <span>
                        {{ trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo . ' - ' : '') . ($apontamento->colaborador?->nome ?? 'Responsável não informado')) }}
                        desde {{ $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-' }}
                        @if ($apontamento->iniciado_em)
                            ({{ $apontamento->iniciado_em->diffForHumans(now(), true) }})
                        @endif
                    </span>
                @endforeach
            @endif
        </div>

        <div class="os-simple-item">
            <strong>Histórico</strong>

            @if ($record->apontamentosOficina->isEmpty())
                <span>Nenhum apontamento registrado.</span>
            @else
                @foreach ($record->apontamentosOficina->sortByDesc('iniciado_em') as $apontamento)
                    @php
                        $servicos = $apontamento->itens
                            ->map(fn ($item) => trim(($item->servico?->codigo ? $item->servico->codigo . ' - ' : '') . ($item->servico?->descricao ?? '')))
                            ->filter()
                            ->join(', ');
                    @endphp

                    <div class="os-simple-item">
                        <strong>{{ trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo . ' - ' : '') . ($apontamento->colaborador?->nome ?? 'Responsável não informado')) }}</strong>
                        <span>Início: {{ $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-' }}</span>
                        <span>Fim: {{ $apontamento->encerrado_em?->format('d/m/Y H:i') ?? 'Aberto' }}</span>
                        <span>
                            Duração:
                            @if ($apontamento->iniciado_em && $apontamento->encerrado_em)
                                {{ $apontamento->iniciado_em->diffForHumans($apontamento->encerrado_em, true) }}
                            @elseif ($apontamento->iniciado_em)
                                {{ $apontamento->iniciado_em->diffForHumans(now(), true) }} em andamento
                            @else
                                -
                            @endif
                        </span>
                        <span>Serviços: {{ $servicos ?: '-' }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
