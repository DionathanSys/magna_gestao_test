<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\BugioPanelProvider;
use App\Providers\Filament\MobilePanelProvider;
use App\Providers\Filament\OficinaPanelProvider;

return [
    AppServiceProvider::class,
    // App\Providers\ServiceServiceProvider::class,
    AdminPanelProvider::class,
    BugioPanelProvider::class,
    MobilePanelProvider::class,
    OficinaPanelProvider::class,
];
