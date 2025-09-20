<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin EZToll',
            'username' => 'admin',
            'nama_lengkap' => 'Administrator EZToll',
            'email' => 'admin@eztoll.com',
            'password' => Hash::make('admin123'),
            'saldo' => 0,
            'is_admin' => true,
        ]);
    }
}