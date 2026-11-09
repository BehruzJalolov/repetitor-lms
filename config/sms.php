<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Eskiz.uz SMS sozlamalari
    |--------------------------------------------------------------------------
    | O'zbekistondagi telefon raqamlarga SMS xabarlar yuborish uchun
    | Eskiz.uz xizmati ma'lumotlari.
    */
    'eskiz' => [
        'email' => env('ESKIZ_EMAIL', null),
        'password' => env('ESKIZ_PASSWORD', null),
        'from' => env('ESKIZ_FROM', '4546'),
        'base_url' => env('ESKIZ_BASE_URL', 'https://notify.eskiz.uz/api'),
    ],
];
