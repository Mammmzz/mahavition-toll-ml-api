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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tarif_id')->constrained()->onDelete('cascade');
            $table->decimal('saldo_pembayaran', 15, 2);
            $table->string('plat_nomor');
            $table->string('jenis_kendaraan');
            $table->string('status')->default('SUCCESS'); // SUCCESS, FAILED
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
