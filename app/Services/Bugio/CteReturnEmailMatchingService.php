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
        if (! $this->config->isAllowedSender($incomingEmail->from_email)) {
            return null;
        }

        $documentoTransporte = $this->config->extractDocumentoTransporte((string) $incomingEmail->subject);

        if (! $documentoTransporte) {
            return null;
        }

        return $this->requestService->findOpenByDocumentoTransporte($documentoTransporte);
    }
}
