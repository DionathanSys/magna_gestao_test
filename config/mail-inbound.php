<?php

return [

    'default_provider' => env('MAIL_INBOUND_PROVIDER', 'imap'),

    'queue' => [
        'ingest' => env('MAIL_INBOUND_QUEUE_INGEST', 'mail-receive'),
        'process' => env('MAIL_INBOUND_QUEUE_PROCESS', 'mail-process'),
        'trip' => env('MAIL_INBOUND_QUEUE_TRIP', 'mail-trip'),
    ],

    'storage' => [
        'disk' => env('MAIL_INBOUND_STORAGE_DISK', 'local'),
        'path' => env('MAIL_INBOUND_STORAGE_PATH', 'private/mail/incoming'),
    ],

    'imap' => [
        'host' => env('IMAP_HOST', 'imap.hostinger.com'),
        'port' => (int) env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', true),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'protocol' => env('IMAP_PROTOCOL', 'imap'),
        'folder' => env('IMAP_DEFAULT_FOLDER', 'INBOX'),
        'mark_as_seen' => (bool) env('MAIL_INBOUND_MARK_AS_SEEN', true),
        'fetch_limit' => (int) env('MAIL_INBOUND_FETCH_LIMIT', 20),
    ],
];
