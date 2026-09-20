<?php

namespace App\Enum\Automation;

enum AutomationJobStatus: string
{
    case PENDING_SUBMISSION = 'PENDING_SUBMISSION';
    case REQUEST_FAILED = 'REQUEST_FAILED';
    case QUEUED = 'QUEUED';
    case RUNNING = 'RUNNING';
    case RETRYING = 'RETRYING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
        ], true);
    }

    public function isAwaitingSubmission(): bool
    {
        return in_array($this, [
            self::PENDING_SUBMISSION,
            self::REQUEST_FAILED,
        ], true);
    }

    public static function fromProvider(string $status): self
    {
        return match (strtoupper($status)) {
            'QUEUED' => self::QUEUED,
            'RUNNING' => self::RUNNING,
            'RETRYING' => self::RETRYING,
            'COMPLETED' => self::COMPLETED,
            'FAILED' => self::FAILED,
            'CANCELLED' => self::CANCELLED,
            default => throw new \InvalidArgumentException("Status de automacao desconhecido: {$status}"),
        };
    }
}
