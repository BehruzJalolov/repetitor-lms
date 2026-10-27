<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin (repetitor) hisobi. Ma'lumotlar config/admin.php (.env) dan olinadi.
        $phone = Phone::normalize(config('admin.phone')) ?? config('admin.phone');

        User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => config('admin.name'),
                'role' => 'admin',
                'password' => config('admin.password'),
            ]
        );
    }
}
