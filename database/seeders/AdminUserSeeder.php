<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed default admin user.
     * Idempotent: tidak membuat duplikat jika sudah ada.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'System'],
            [
                'password' => 'qcqcbmi', // otomatis di-hash oleh cast 'hashed'
                'role' => 'admin',
            ]
        );
    }
}
