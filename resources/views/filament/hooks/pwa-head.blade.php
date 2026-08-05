@php
    $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'admin';

    $pwa = [
        'admin' => [
            'name' => 'Magna Gestao Admin',
            'manifest' => 'manifest-admin.webmanifest',
        ],
        'mobile' => [
            'name' => 'Magna Mobile',
            'manifest' => 'manifest-mobile.webmanifest',
        ],
        'oficina' => [
            'name' => 'Magna Oficina',
            'manifest' => 'manifest-oficina.webmanifest',
        ],
        'bugio' => [
            'name' => 'Axion Soft Gestao',
            'manifest' => 'manifest-bugio.webmanifest',
        ],
    ][$panelId] ?? [
        'name' => 'Magna Gestao',
        'manifest' => 'manifest.webmanifest',
    ];
@endphp

<meta name="application-name" content="{{ $pwa['name'] }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $pwa['name'] }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#18181b">
<meta name="msapplication-TileColor" content="#18181b">
<link rel="manifest" href="{{ asset($pwa['manifest']) }}">
<link rel="icon" href="{{ asset('icons/app-icon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('icons/app-icon-192.png') }}" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('icons/app-icon-180.png') }}">
