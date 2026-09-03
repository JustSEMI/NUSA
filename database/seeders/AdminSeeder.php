<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Buat atau perbarui akun admin default.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nusa.ai'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('admin123456'),
                'is_admin' => true,
            ]
        );

        $this->command->info('Admin account ready: admin@nusa.ai / admin123456');
    }
}
