<?php

namespace App\Contracts;

interface MailInboundProvider
{
    public function fetchNewMessages(): iterable;
}
