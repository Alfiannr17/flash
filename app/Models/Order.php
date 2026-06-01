<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Ini wajib ditambahin biar kita bisa insert data ke tabel ini nanti
 protected $fillable = [
    'user_id', 'service_code', 'activation_id', 'phone_number', 
    'otp_code', 'price', 'status', 'country_id', 
    'api_version', 'selected_id', 'payment_ref', 'payment_qr' // <--- PASTIKAN ADA INI
];
public function user()
    {
        return $this->belongsTo(User::class);
    }
}