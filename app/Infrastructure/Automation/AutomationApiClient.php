<?php

namespace App\Infrastructure\Automation;

use App\Domain\Automation\AutomationReportDefinition;
use App\Domain\Automation\Exceptions\AutomationApiException;
use App\Models\AutomationJob;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class AutomationApiClient
{
    public function __construct(
        private readonly HmacRequestSigner $signer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createJob(AutomationJob $job, AutomationReportDefinition $definition): array
    {
        $path = '/api/v1/jobs';
        $payload = [
            'collector' => $definition->collector,
            'parameters' => $job->parameters ?? [],
            'requested_by' => $job->requested_by_user_id !== null
                ? 'user:'.$job->requested_by_user_id
                : 'system:'.$job->source->value,
            'metadata' => [
                ...($job->metadata ?? []),
                'local_job_id' => (string) $job->id,
                'report_key' => $job->report_key,
                'collector_version' => $definition->collectorVersion,
                'schema_version' => $definition->schemaVersion,
            ],
        ];

        $response = $this->request('POST', $path, $payload, $job->request_id, $job->idempotency_key);
        $data = $response['data'] ?? null;

        if (! is_array($data) || blank($data['id'] ?? null) || blank($data['status'] ?? null)) {
            throw new AutomationApiException(
                message: 'Resposta invalida da Automation API ao criar job.',
                retryable: false,
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getJob(string $providerJobId, string $requestId): array
    {
        return $this->request(
            'GET',
            '/api/v1/jobs/'.rawurlencode($providerJobId),
            [],
            $requestId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getCollectors(string $requestId): array
    {
        return $this->request('GET', '/api/v1/collectors', [], $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getResultPage(
        string $providerJobId,
        ?string $cursor,
        int $limit,
        string $requestId,
    ): array {
        $query = ['limit' => max(1, $limit)];

        if ($cursor !== null) {
            $query['cursor'] = $cursor;
        }

        $path = '/api/v1/jobs/'.rawurlencode($providerJobId).'/result?'.http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986,
        );

        return $this->request('GET', $path, [], $requestId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $path,
        array $payload,
        string $requestId,
        ?string $idempotencyKey = null,
    ): array {
        $baseUrl = trim((string) config('automation.api.base_url'));

        if ($baseUrl === '') {
            throw new AutomationApiException('URL da Automation API nao configurada.');
        }

        try {
            $body = $method === 'GET'
                ? ''
                : json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $headers = $this->signer->headers($method, $path, $body, $requestId);

            $pendingRequest = Http::baseUrl(rtrim($baseUrl, '/'))
                ->timeout((int) config('automation.api.timeout_seconds', 30))
                ->connectTimeout((int) config('automation.api.connect_timeout_seconds', 5))
                ->withHeaders($headers);

            if ($idempotencyKey !== null) {
                $pendingRequest = $pendingRequest->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }

            $response = $method === 'GET'
                ? $pendingRequest->get($path)
                : $pendingRequest->withBody($body, 'application/json')->post($path);
        } catch (AutomationApiException $exception) {
            throw $exception;
        } catch (ConnectionException|JsonException $exception) {
            throw new AutomationApiException(
                message: 'Nao foi possivel comunicar com a Automation API.',
                retryable: true,
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new AutomationApiException(
                message: 'Falha inesperada ao comunicar com a Automation API.',
                retryable: false,
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw AutomationApiException::fromResponse($response);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new AutomationApiException(
                message: 'Resposta invalida da Automation API.',
                statusCode: $response->status(),
                retryable: false,
            );
        }

        return $json;
    }
}
