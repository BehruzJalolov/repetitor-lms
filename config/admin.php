<?php

return [
    /*
    | Admin (repetitor) hisobi — `php artisan db:seed` orqali yaratiladi.
    | Qiymatlar .env faylidan olinadi (env() faqat config ichida ishlatiladi,
    | shunda `php artisan optimize` dan keyin ham to'g'ri ishlaydi).
    */
    'name' => env('ADMIN_NAME', 'Repetitor'),
    'phone' => env('ADMIN_PHONE', '+998901234567'),
    'email' => env('ADMIN_EMAIL', 'admin@repetitor.uz'),
    'password' => env('ADMIN_PASSWORD', 'admin12345'),

    /*
    | To'lov qabul qilinadigan karta (o'quvchiga to'lov oynasida ko'rsatiladi).
    */
    'card_number' => env('PAYMENT_CARD_NUMBER', '8600 0000 0000 0000'),
    'card_holder' => env('PAYMENT_CARD_HOLDER', 'Repetitor'),
];
