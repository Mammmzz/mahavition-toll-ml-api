<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Tarifs
        \App\Models\Tarif::create([
            'kelompok_kendaraan' => 'ambulan',
            'harga' => 5000
        ]);
        
        \App\Models\Tarif::create([
            'kelompok_kendaraan' => 'Mobil',
            'harga' => 10000
        ]);
        
        \App\Models\Tarif::create([
            'kelompok_kendaraan' => 'Bus',
            'harga' => 15000
        ]);
        
        \App\Models\Tarif::create([
            'kelompok_kendaraan' => 'Truk',
            'harga' => 20000
        ]);

        // Seed Users
        \App\Models\User::create([
            'username' => 'johndoe',
            'nama_lengkap' => 'John Doe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 4040000,
            'plat_nomor' => 'B 8005 FAI',
            'alamat' => 'Jl. Jakarta No. 123',
            'no_telp' => '08123456789'
        ]);

        \App\Models\User::create([
            'username' => 'janedoe',
            'nama_lengkap' => 'Jane Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 955000,
            'plat_nomor' => 'H 4534 HR',
            'alamat' => 'Jl. Bandung No. 456',
            'no_telp' => '089876543210'
        ]);

        \App\Models\User::create([
            'username' => 'test1',
            'nama_lengkap' => 'Pengguna Test Satu',
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 135000,
            'plat_nomor' => 'B 5432 KRI',
            'alamat' => 'Jalan Test No. 1',
            'no_telp' => '08123456789'
        ]);

        \App\Models\User::create([
            'username' => 'test2',
            'nama_lengkap' => 'Pengguna Test Dua',
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 110000,
            'plat_nomor' => 'W 8390 SE',
            'alamat' => 'Jalan Test No. 2',
            'no_telp' => '08987654321'
        ]);

        \App\Models\User::create([
            'username' => 'test3',
            'nama_lengkap' => 'Pengguna Test Tiga',
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 80000,
            'plat_nomor' => 'N 8475 PK',
            'alamat' => 'Jalan Test No. 3',
            'no_telp' => '08111222333'
        ]);
        
        // Tambahan 5 user baru
        \App\Models\User::create([
            'username' => 'user4',
            'nama_lengkap' => 'Ahmad Rizki',
            'name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 250000,
            'plat_nomor' => 'B 1122 JKT',
            'alamat' => 'Jl. Sudirman No. 45',
            'no_telp' => '081234567891'
        ]);
        
        \App\Models\User::create([
            'username' => 'user5',
            'nama_lengkap' => 'Siti Nurhayati',
            'name' => 'Siti Nurhayati',
            'email' => 'siti@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 175000,
            'plat_nomor' => 'D 2233 BDG',
            'alamat' => 'Jl. Asia Afrika No. 78',
            'no_telp' => '089876543211'
        ]);
        
        \App\Models\User::create([
            'username' => 'user6',
            'nama_lengkap' => 'Budi Santoso',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 320000,
            'plat_nomor' => 'F 3344 SMG',
            'alamat' => 'Jl. Pemuda No. 12',
            'no_telp' => '081122334455'
        ]);
        
        \App\Models\User::create([
            'username' => 'user7',
            'nama_lengkap' => 'Dewi Lestari',
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 185000,
            'plat_nomor' => 'T 4455 MLG',
            'alamat' => 'Jl. Soekarno Hatta No. 90',
            'no_telp' => '089988776655'
        ]);
        
        \App\Models\User::create([
            'username' => 'user8',
            'nama_lengkap' => 'Eko Prasetyo',
            'name' => 'Eko Prasetyo',
            'email' => 'eko@example.com',
            'password' => \Hash::make('password123'),
            'saldo' => 275000,
            'plat_nomor' => 'L 5566 SBY',
            'alamat' => 'Jl. Darmo No. 33',
            'no_telp' => '081234987654'
        ]);
    }
}