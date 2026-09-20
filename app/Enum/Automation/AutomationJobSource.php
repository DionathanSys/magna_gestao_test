<?php

namespace App\Enum\Automation;

enum AutomationJobSource: string
{
    case MANUAL = 'manual';
    case SCHEDULED = 'scheduled';
}
