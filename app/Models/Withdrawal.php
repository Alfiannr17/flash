<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'amount',
        'fee',
        'destination_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}