<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifikasi tabel users yang sudah ada
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('nama_lengkap')->after('username');
            $table->decimal('saldo', 15, 2)->default(0)->after('email');
            $table->string('plat_nomor')->unique()->nullable()->after('saldo');
            $table->text('alamat')->nullable()->after('plat_nomor');
            $table->string('no_telp')->nullable()->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'nama_lengkap', 'saldo', 'plat_nomor', 'alamat', 'no_telp']);
        });
    }
};
