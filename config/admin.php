<?php

return [
    /*
    | Admin (repetitor) hisobi — `php artisan db:seed` orqali yaratiladi.
    | Qiymatlar .env faylidan olinadi (env() faqat config ichida ishlatiladi,
    | shunda `php artisan optimize` dan keyin ham to'g'ri ishlaydi).
    */
    'name' => env('ADMIN_NAME', 'Repetitor'),
    'phone' => env('ADMIN_PHONE', '+998901234567'),
    'password' => env('ADMIN_PASSWORD', 'admin12345'),
];
