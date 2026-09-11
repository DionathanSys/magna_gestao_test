<?php

namespace App\Http\Controllers;

use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardService;
use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardShareService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResultadoPeriodoDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        ResultadoPeriodoDashboardShareService $shareService,
        ResultadoPeriodoDashboardService $dashboardService,
    ): View|Response {
        if (! $request->hasValidSignature()) {
            return response()->view('resultado-periodo.dashboard-invalid', [
                'titulo' => 'Link inválido ou expirado',
                'mensagem' => 'Solicite um novo link de acesso ao responsável pelo relatório.',
            ], 403);
        }

        $share = $shareService->resolve($token);

        if (! $share) {
            return response()->view('resultado-periodo.dashboard-invalid', [
                'titulo' => 'Acesso expirado',
                'mensagem' => 'Este link não está mais disponível. Solicite um novo compartilhamento.',
            ], 410);
        }

        $records = $dashboardService->recordsFor($share->resultado_periodo_ids ?? []);

        if ($records->isEmpty()) {
            return response()->view('resultado-periodo.dashboard-invalid', [
                'titulo' => 'Dados indisponíveis',
                'mensagem' => 'Os resultados vinculados a este link não estão mais disponíveis.',
            ], 404);
        }

        return response()
            ->view('resultado-periodo.dashboard', [
                'share' => $share,
                'dashboard' => $dashboardService->summarize($records),
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
