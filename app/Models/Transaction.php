<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // Tambahkan kolom yang bisa diisi
    protected $fillable = [
        'card_id', 
        'amount', 
        'description', 
        'transaction_date'
    ];

    // Nonaktifkan timestamps jika tidak diperlukan
    public $timestamps = false;

    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id', 'card_id');
    }
}

