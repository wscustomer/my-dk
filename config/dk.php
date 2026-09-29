<?php

return [
    // Akun admin awal yang dibuat seeder (DatabaseSeeder).
    'admin_email' => env('DK_ADMIN_EMAIL', 'admin@digitalkonsultan.com'),
    'admin_password' => env('DK_ADMIN_PASSWORD'),

    // Sumber penagihan WSCRM — baca-saja, dipakai dk:tarik-wscrm.
    'wscrm_url' => env('WSCRM_URL'),
    'wscrm_token' => env('WSCRM_TOKEN'),
];
