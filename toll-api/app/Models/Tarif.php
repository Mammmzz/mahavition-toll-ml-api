<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tarif extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'kelompok_kendaraan',
        'harga',
    ];
    
    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }
    
    /**
     * Relationship with transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
