<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait UserCheckTrait
{
    protected int $systemUserId = 7;

    public function getUserIdChecked(): int
    {
        return Auth::check() ? Auth::user()->id : $this->getSystemUserId();
    }

    protected function getSystemUserId(): int
    {
        return $this->systemUserId;
    }

    public function setSystemUserId(int $userId): self
    {
        $this->systemUserId = $userId;
        return $this;
    }
}
