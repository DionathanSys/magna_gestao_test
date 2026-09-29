<?php

namespace App\Enum\Automation;

enum AutomationResultImportStatus: string
{
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
}
