<?php

namespace App\Enum\Automation;

enum AutomationEventStatus: string
{
    case RECEIVED = 'RECEIVED';
    case PROCESSING = 'PROCESSING';
    case IMPORTING = 'IMPORTING';
    case PROCESSED = 'PROCESSED';
    case FAILED = 'FAILED';
}
