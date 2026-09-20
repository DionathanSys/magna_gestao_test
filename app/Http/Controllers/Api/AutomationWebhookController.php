<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Automation\ProcessAutomationEvent;
use App\Models\AutomationEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JsonException;

class AutomationWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_JSON',
                    'message' => 'Corpo JSON invalido.',
                    'request_id' => $request->header('X-Request-ID'),
                ],
            ], 400);
        }

        if (! is_array($payload)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_JSON',
                    'message' => 'O corpo JSON deve ser um objeto.',
                    'request_id' => $request->header('X-Request-ID'),
                ],
            ], 400);
        }

        $validator = Validator::make($payload, [
            'event_id' => 'required|string|max:120',
            'event' => 'required|string|max:80',
            'occurred_at' => 'required|date',
            'job.id' => 'required|string|max:120',
            'job.status' => 'required|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Evento de automacao invalido.',
                    'details' => $validator->errors()->toArray(),
                    'request_id' => $request->header('X-Request-ID'),
                ],
            ], 422);
        }

        $eventId = (string) $payload['event_id'];
        $existing = AutomationEvent::query()->where('event_id', $eventId)->first();

        if ($existing) {
            return $this->duplicateResponse($eventId);
        }

        try {
            $event = DB::transaction(function () use ($payload, $request): AutomationEvent {
                $event = AutomationEvent::query()->create([
                    'event_id' => (string) $payload['event_id'],
                    'provider_job_id' => (string) data_get($payload, 'job.id'),
                    'client_id' => (string) $request->header('X-Client-ID'),
                    'event_type' => (string) $payload['event'],
                    'payload' => $payload,
                    'occurred_at' => $payload['occurred_at'],
                    'received_at' => now(),
                    'processing_status' => 'RECEIVED',
                    'request_id' => $request->header('X-Request-ID'),
                ]);

                ProcessAutomationEvent::dispatch($event->id)
                    ->onQueue((string) config('automation.queues.processing', 'automation-import'))
                    ->afterCommit();

                return $event;
            });
        } catch (QueryException $exception) {
            if (! AutomationEvent::query()->where('event_id', $eventId)->exists()) {
                throw $exception;
            }

            return $this->duplicateResponse($eventId);
        }

        return response()->json([
            'received' => true,
            'event_id' => $event->event_id,
        ], 202);
    }

    private function duplicateResponse(string $eventId): JsonResponse
    {
        return response()->json([
            'received' => true,
            'duplicate' => true,
            'event_id' => $eventId,
        ]);
    }
}
