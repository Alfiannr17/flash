<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\SmsBowerService;

class PriceController extends Controller
{
    // Fungsi untuk menampilkan halaman Daftar Harga
    public function index(SmsBowerService $smsBower)
    {
        // Kita passing data layanan dan negara ke halaman Daftar Harga
        // Biar user bisa cek harga sebelum masuk ke menu Order
        return Inertia::render('Prices/Index', [
            'services'  => $smsBower->getServicesList(),
            'countries' => $smsBower->getCountries()
        ]);
    }
}