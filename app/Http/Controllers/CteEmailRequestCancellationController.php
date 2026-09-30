<?php

namespace App\Http\Controllers;

use App\Models\CteEmailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CteEmailRequestCancellationController extends Controller
{
    public function show(CteEmailRequest $cteEmailRequest): View
    {
        return view('cte-email-requests.cancel', [
            'cteRequest' => $cteEmailRequest->load('viagem'),
        ]);
    }

    public function cancel(Request $request, CteEmailRequest $cteEmailRequest): View
    {
        $cancelled = DB::transaction(function () use ($cteEmailRequest): bool {
            return CteEmailRequest::query()
                ->whereKey($cteEmailRequest->id)
                ->where('status', 'pending_send')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Cancelada pelo link do Telegram.',
                    'error_message' => null,
                ]) === 1;
        });

        return view('cte-email-requests.cancel-result', [
            'cteRequest' => $cteEmailRequest->fresh('viagem'),
            'cancelled' => $cancelled,
        ]);
    }
}
