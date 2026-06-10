<?php

namespace App\Services\Bugio;

use App\Models\CteEmailRequest;
use App\Models\IncomingEmail;

class CteReturnEmailMatchingService
{
    public function __construct(
        protected CteReturnConfig $config,
        protected CteEmailRequestService $requestService,
    ) {}

    public function match(IncomingEmail $incomingEmail): ?CteEmailRequest
    {
        $match = $this->matchWithStrategy($incomingEmail);

        return $match['request'] ?? null;
    }

    /**
     * @return array{request: CteEmailRequest, matched_by: string}|null
     */
    public function matchWithStrategy(IncomingEmail $incomingEmail): ?array
    {
        if (! $this->config->isAllowedSender($incomingEmail->from_email)) {
            return null;
        }

        if ($incomingEmail->in_reply_to) {
            $request = $this->requestService->findOpenByMessageId($incomingEmail->in_reply_to);

            if ($request) {
                return [
                    'request' => $request,
                    'matched_by' => 'in_reply_to',
                ];
            }
        }

        $references = $this->extractReferences($incomingEmail->references_header);

        if ($references !== []) {
            $request = $this->requestService->findOpenByReferencedMessageIds($references);

            if ($request) {
                return [
                    'request' => $request,
                    'matched_by' => 'references',
                ];
            }
        }

        $correlationCode = $this->config->extractCorrelationCode((string) $incomingEmail->subject);

        if ($correlationCode) {
            $request = $this->requestService->findOpenByCorrelationCode($correlationCode);

            if ($request) {
                return [
                    'request' => $request,
                    'matched_by' => 'correlation_code_subject',
                ];
            }
        }

        $documentoTransporte = $this->config->extractDocumentoTransporte((string) $incomingEmail->subject);

        if (! $documentoTransporte) {
            return null;
        }

        $request = $this->requestService->findOpenByDocumentoTransporte($documentoTransporte);

        if (! $request) {
            return null;
        }

        return [
            'request' => $request,
            'matched_by' => 'documento_transporte_subject',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function extractReferences(?string $referencesHeader): array
    {
        if (! $referencesHeader) {
            return [];
        }

        preg_match_all('/<?([^<>\s]+)>?/', $referencesHeader, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $reference): string => trim($reference))
            ->filter()
            ->values()
            ->all();
    }
}
