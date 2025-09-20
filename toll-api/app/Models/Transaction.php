<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'tarif_id',
        'saldo_pembayaran',
        'plat_nomor',
        'jenis_kendaraan',
        'status',
        'created_at',
        'updated_at',
    ];
    
    protected function casts(): array
    {
        return [
            'saldo_pembayaran' => 'decimal:2',
        ];
    }
    
    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Relationship with tarif
     */
    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }
}
