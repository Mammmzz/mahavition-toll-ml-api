<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateCondition extends Model
{
    use HasFactory;

    // Kalau nama tabel tidak jamak, kita set manual
    protected $table = 'gate_condition';

    // Kolom yang boleh diisi mass-assignment
    protected $fillable = [
        'gate_status',
        'saldo',
    ];

    // Default value (opsional, biar aman)
    protected $attributes = [
        'gate_status' => 'OFF',
        'saldo' => 0,
    ];
}
