<?php

namespace App\Events\MailInbound;

class IncomingEmailStored
{
    public function __construct(public int $incomingEmailId)
    {
    }
}
