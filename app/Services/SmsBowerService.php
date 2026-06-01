<?php

namespace App\Services;

// ... (kode lainnya)

use Illuminate\Support\Facades\Http;

class SmsBowerService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('SMSBOWER_API_KEY');
        // Base URL dari dokumentasi API SMSBower
        $this->baseUrl = 'https://smsbower.page/stubs/handler_api.php';
    }

    // 1. Cek Saldo Akun SMSBower Lu
    public function getBalance()
    {
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action'  => 'getBalance'
        ]);
        
        $result = $response->body();
        
        // Format balasannya: ACCESS_BALANCE:account balance
        if (str_starts_with($result, 'ACCESS_BALANCE')) {
            return explode(':', $result)[1];
        }

        return 0;
    }

    // 2. Beli Nomor Nokos
    // Tambahkan parameter $extraParams
    public function getNumber($service, $country = '0', $extraParams = []) 
    {
        $params = [
            'api_key' => $this->apiKey,
            'action'  => 'getNumber',
            'service' => $service,
            'country' => $country
        ];

        // Gabungkan parameter tambahan (kalau user milih pakai V2 atau V3)
        $params = array_merge($params, $extraParams);

        $response = Http::get($this->baseUrl, $params);
        $result = $response->body();
        
        if (str_starts_with($result, 'ACCESS_NUMBER')) {
            $parts = explode(':', $result);
            return [
                'success' => true,
                'activation_id' => $parts[1],
                'phone_number' => $parts[2]
            ];
        }

        return ['success' => false, 'error' => $result];
    }

    // 3. Cek Status / Ambil Kode OTP
    public function getStatus($activationId)
    {
        // action getStatus dipanggil dengan parameter id aktivasi
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action'  => 'getStatus',
            'id'      => $activationId
        ]);

        $result = $response->body();
        
        // Format nunggu OTP: STATUS_WAIT_CODE
        // Format OTP masuk: STATUS_OK:'kode otp'
        if (str_starts_with($result, 'STATUS_OK')) {
            return [
                'status' => 'completed',
                'code'   => explode(':', $result)[1]
            ];
        }

        return [
            'status' => 'waiting', 
            'raw'    => $result
        ];
    }

    // 4. Batalkan Pesanan (Cancel)
    public function cancelNumber($activationId)
    {
        // Status 8 = cancel activation
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action'  => 'setStatus',
            'status'  => '8',
            'id'      => $activationId
        ]);

        // Mengembalikan ACCESS_CANCEL jika berhasil dibatalkan
        return $response->body();
    }


    // Ambil daftar semua layanan dari API SMSBower
    public function getServicesList()
    {
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action'  => 'getServicesList' // [cite: 137]
        ]);

        // Paksa terjemahkan string murni jadi array PHP
        $result = json_decode($response->body(), true);

        // Kalau sukses, kembalikan daftar layanannya [cite: 143, 144]
        if (isset($result['status']) && $result['status'] === 'success') {
            return $result['services'];
        }

        return [];
    }

    // Ambil daftar negara dari API
    public function getCountries()
    {
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action'  => 'getCountries'
        ]);

        return json_decode($response->body(), true) ?? [];
    }

    // Ambil harga realtime dari API
    /**
     * 1. Get Prices (V1)
     * Mengembalikan 1 harga (cost) dan total stok (count)
     */
    public function getPrices($service = null, $country = null)
    {
        $params = [
            'api_key' => $this->apiKey,
            'action'  => 'getPrices'
        ];
        
        // Parameter opsional, kalau nggak diisi bakal narik SEMUA harga layanan
        if ($service) $params['service'] = $service;
        if ($country) $params['country'] = $country;

        $response = Http::get($this->baseUrl, $params);
        $result = json_decode($response->body(), true);

        return $result;
    }

    /**
     * 2. Get Prices V2
     * Mengembalikan list harga sebagai key, dan stok sebagai value. 
     * Contoh: {"10.5": 50, "11.0": 100}
     */
    public function getPricesV2($service = null, $country = null)
    {
        $params = [
            'api_key' => $this->apiKey,
            'action'  => 'getPricesV2' // Asumsi penamaan action dari dokumentasi
        ];
        
        if ($service) $params['service'] = $service;
        if ($country) $params['country'] = $country;

        $response = Http::get($this->baseUrl, $params);
        $result = json_decode($response->body(), true);

        return $result;
    }

    /**
     * 3. Get Prices V3
     * Mengembalikan list harga detail berdasarkan ID Provider.
     */
    public function getPricesV3($service = null, $country = null)
    {
        $params = [
            'api_key' => $this->apiKey,
            'action'  => 'getPricesV3'
        ];
        
        if ($service) $params['service'] = $service;
        if ($country) $params['country'] = $country;

        $response = Http::get($this->baseUrl, $params);
        $result = json_decode($response->body(), true);

        return $result;
    }

    /**
     * FUNGSI HELPER: Ambil 1 harga paling murah untuk di-display ke user
     * (Sistemnya kita pakai V1 aja biar simpel buat user)
     */
    public function getLowestPrice($service, $country)
    {
        $prices = $this->getPrices($service, $country);

        // Cek format balasan V1: {"CountryID": {"ServiceID": {"cost": 10, "count": 100}}}
        if (isset($prices[$country][$service]['cost'])) {
            return $prices[$country][$service]['cost'];
        }

        return false; // Kalau stok kosong
    }
}