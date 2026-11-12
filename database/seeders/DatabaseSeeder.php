<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Membuat akun admin awal. Segera ganti password setelah login pertama.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'password' => 'admin12345', 'is_admin' => true],
        );
    }
}
