<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\SmsBowerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function create(SmsBowerService $smsBower)
    {
        // ==========================================
        // JURUS ANTI DOUBLE ORDER (CEK ORDER AKTIF)
        // ==========================================
        $activeOrder = Order::where('user_id', auth()->id())
            ->whereIn('status', ['waiting_otp', 'pending_payment'])
            ->first();

        if ($activeOrder) {
            // Kalau masih ada order aktif, tendang balik ke halaman order tersebut!
            return redirect()->route('order.waiting', $activeOrder->id)
                ->withErrors(['error' => 'Selesaikan atau batalkan pesanan aktif ini terlebih dahulu sebelum membuat pesanan baru!']);
        }

        $countries = [];
        $allServices = [];

        try {
            $countries = $smsBower->getCountries();
            $rawServices = $smsBower->getServicesList();

            if (is_array($rawServices)) {
                foreach ($rawServices as $code => $name) {
                    $allServices[] = [
                        'c' => (string)$code,
                        'n' => (string)$name
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('SMSBower Down: ' . $e->getMessage());
        }

        return Inertia::render('Order/Create', [
            'countries' => $countries,
            'allServices' => $allServices
        ]);
    }

    // 2. Endpoint buat mengecek harga
    public function checkPrice(Request $request, SmsBowerService $smsBower)
    {
        $service = $request->service_code;
        $country = $request->country_id;
        $version = $request->api_version ?? 'v1';
        $options = [];

        // Gunakan config() sebagai backup terbaik untuk env() di Controller
        $usdRate = config('services.smsbower.usd_rate', env('SMSBOWER_USD_RATE', 16000));
        $profitMargin = config('services.smsbower.profit_margin', env('NOKOS_PROFIT_MARGIN', 2000));  

        // ==========================================
        // 🌟 JURUS STEALTH: DISKON 60% JIKA SALDO 0
        // ==========================================
        $userBalance = auth()->user() ? auth()->user()->balance : 0;
        $isPromo = $userBalance <= 0;

        $kalkulasiHargaJual = function($hargaApiAsli) use ($usdRate, $profitMargin, $isPromo) {
            $normalPrice = round(((float)$hargaApiAsli * $usdRate) + $profitMargin);
            return $isPromo ? round($normalPrice * 0.6) : $normalPrice; // Potong 60% harga asli
        };
        // ==========================================

        if ($version === 'v1') {
            $data = $smsBower->getPrices($service, $country);
            if (isset($data[$country][$service]['cost'])) {
                $options[] = [
                    'id'    => 'default',
                    'name'  => 'Harga Rata-rata Server',
                    'price' => $kalkulasiHargaJual($data[$country][$service]['cost']),
                    'count' => $data[$country][$service]['count'] ?? 0,
                ];
            }
        } 
        elseif ($version === 'v2') {
            $data = $smsBower->getPricesV2($service, $country);
            if (isset($data[$country][$service]) && is_array($data[$country][$service])) {
                foreach ($data[$country][$service] as $price => $count) {
                    $options[] = [
                        'id'    => (string)$price,
                        'name'  => 'Server Stabil #' . rand(10, 99),
                        'price' => $kalkulasiHargaJual($price),
                        'count' => $count,
                    ];
                }
            }
        } 
        elseif ($version === 'v3') {
            $data = $smsBower->getPricesV3($service, $country);
            if (isset($data[$country][$service]) && is_array($data[$country][$service])) {
                foreach ($data[$country][$service] as $providerId => $info) {
                    if (isset($info['price'])) {
                        $options[] = [
                            'id'    => (string)$providerId,
                            'name'  => 'Provider ID: ' . $providerId,
                            'price' => $kalkulasiHargaJual($info['price']),
                            'count' => $info['count'] ?? 0,
                        ];
                    }
                }
            }
        }
        elseif ($version === 'v4') {
            $response = Http::get('https://hero-sms.com/stubs/handler_api.php', [
                'action'  => 'getPrices',
                'service' => $service,
                'country' => $country,
                'api_key' => env('HERO_OTP_API_KEY')
            ]);

            $data = $response->json();

            if (isset($data[$country][$service])) {
                $info = $data[$country][$service];
                $hargaJual = ceil($info['cost'] * 16000 + 2127); // Rumus harga V4 yang sudah disesuaikan

                $options[] = [
                    'id'    => 'v4_' . $service . '_' . $country, 
                    'name'  => 'Server Hero (V4)',
                    'price' => $hargaJual,
                    'count' => $info['count'] ?? 0,     
                ];
            }
        }

        if (!empty($options)) {
            usort($options, fn($a, $b) => $a['price'] <=> $b['price']);
            return response()->json(['success' => true, 'options' => $options]);
        }
        return response()->json(['success' => false]);
    }

    public function store(Request $request, SmsBowerService $smsBower)
    {
        $request->validate([
            'service_code'   => 'required|string',
            'country_id'     => 'required',
            'selected_id'    => 'required',
            'selected_price' => 'required|numeric',
            'api_version'    => 'required|string',
            'payment_method' => 'required|in:balance,qris'
        ]);

        // ==========================================
        // GEMBOK KEDUA BUAT JAGA-JAGA DARI HACKER
        // ==========================================
        $activeOrder = Order::where('user_id', auth()->id())
            ->whereIn('status', ['waiting_otp', 'pending_payment'])
            ->first();

        if ($activeOrder) {
            return redirect()->route('order.waiting', $activeOrder->id)
                ->withErrors(['error' => 'Anda masih memiliki pesanan aktif!']);
        }

        $paymentRef = 'INV-' . time() . '-' . auth()->id();

        // 1. TARIK KURS DAN MARGIN (Bisa lu ubah-ubah di .env)
        $usdRate = config('services.smsbower.usd_rate', env('SMSBOWER_USD_RATE', 16000));
        $profitMargin = config('services.smsbower.profit_margin', env('NOKOS_PROFIT_MARGIN', 2000));

        // 2. CEK HARGA ASLI DI BELAKANG LAYAR (ANTI HACKER INSPECT ELEMENT)
        $realPriceUsd = 0;

        if ($request->api_version === 'v1') {
            $data = $smsBower->getPrices($request->service_code, $request->country_id);
            $realPriceUsd = $data[$request->country_id][$request->service_code]['cost'] ?? 0;
        } elseif ($request->api_version === 'v2') {
            $realPriceUsd = (float)$request->selected_id; 
        } elseif ($request->api_version === 'v3') {
            $data = $smsBower->getPricesV3($request->service_code, $request->country_id);
            $realPriceUsd = $data[$request->country_id][$request->service_code][$request->selected_id]['price'] ?? 0;
        } elseif ($request->api_version === 'v4') {
            // 🌟 LOGIKA TARIK HARGA V4
            $response = Http::get('https://hero-sms.com/stubs/handler_api.php', [
                'action'  => 'getPrices',
                'service' => $request->service_code,
                'country' => $request->country_id,
                'api_key' => env('HERO_OTP_API_KEY')
            ]);
            $data = $response->json();
            $realPriceUsd = $data[$request->country_id][$request->service_code]['cost'] ?? 0;
        }

        // Kalau API lagi down atau harga ga ketemu
        if ($realPriceUsd <= 0) {
            return back()->withErrors(['error' => 'Layanan sedang gangguan, gagal mengambil harga.']);
        }

        // 3. RUMUS KURS PASTI
        if ($request->api_version === 'v4') {
            // Rumus V4 disamain dengan checkPrice biar frontend dan backend sinkron
            $serverPrice = ceil($realPriceUsd * 16000 + 2127); 
        } else {
            // Rumus V1, V2, V3
            $serverPrice = round(($realPriceUsd * $usdRate) + $profitMargin);
        }

        // ==========================================
        // 🌟 JURUS STEALTH: DISKON 60% JIKA SALDO 0
        // ==========================================
        $user = User::where('id', auth()->id())->first();
        if ($user && $user->balance <= 0) {
            $serverPrice = round($serverPrice * 0.4); 
        }
        // ==========================================

        // PEMBAYARAN SALDO LOKAL
        if ($request->payment_method === 'balance') {
            
            DB::beginTransaction();
            try {
                $userLock = User::where('id', auth()->id())->lockForUpdate()->first();

                // Kita pakai $serverPrice, BUKAN harga dari frontend ($request->selected_price)
                if ($userLock->balance < $serverPrice) {
                    DB::rollBack();
                    return back()->withErrors(['error' => "Saldo tidak cukup! Harga asli: Rp " . number_format($serverPrice)]);
                }

                // Potong saldo sesuai hitungan server
                $userLock->decrement('balance', $serverPrice);

                // 4. KUNCI ORDERAN BIAR API GAK NGASIH HARGA RANDOM
                if ($request->api_version === 'v4') {
                    // 🌟 TEMBAK API HERO-OTP KHUSUS V4
                    $v4Response = Http::get('https://hero-sms.com/stubs/handler_api.php', [
                        'action'  => 'getNumberV2',
                        'service' => $request->service_code,
                        'country' => $request->country_id,
                        'api_key' => env('HERO_OTP_API_KEY')
                    ]);
                    $v4Data = $v4Response->json();
                    
                    if (isset($v4Data['activationId'])) {
                        $apiResult = [
                            'success'       => true,
                            'activation_id' => $v4Data['activationId'],
                            'phone_number'  => $v4Data['phoneNumber']
                        ];
                    } else {
                        $apiResult = ['success' => false, 'error' => 'Stok HeroOTP Kosong/Gangguan'];
                    }
                } else {
                    // 🌟 TEMBAK API SMSBOWER (V1, V2, V3)
                    $extraParams = [];
                    if ($request->api_version === 'v2') { 
                        $extraParams['maxPrice'] = $request->selected_id; 
                    } elseif ($request->api_version === 'v3') { 
                        $extraParams['providerIds'] = $request->selected_id; 
                    }
                    $apiResult = $smsBower->getNumber($request->service_code, $request->country_id, $extraParams);
                }

                if ($apiResult['success']) {
                    $order = Order::create([
                        'user_id'       => $userLock->id,
                        'service_code'  => $request->service_code,
                        'api_version'   => $request->api_version, // 🌟 WAJIB: Biar Check OTP tau ini V4!
                        'country_id'    => $request->country_id,
                        'price'         => $serverPrice, // Simpan harga server
                        'payment_ref'   => $paymentRef,
                        'activation_id' => $apiResult['activation_id'],
                        'phone_number'  => $apiResult['phone_number'],
                        'status'        => 'waiting_otp'
                    ]);
                    
                    DB::commit();
                    return redirect()->route('order.waiting', $order->id);
                } else {
                    DB::rollBack(); 
                    return back()->withErrors(['error' => 'Gagal mengambil nomor: ' . ($apiResult['error'] ?? 'Server Sibuk')]);
                }
            } catch (\Exception $e) {
                DB::rollBack(); 
                Log::error("Error Store Order: " . $e->getMessage());
                return back()->withErrors(['error' => 'Terjadi kesalahan sistem. Saldo aman.']);
            }
        }

        // PEMBAYARAN QRIS (PAKASIR)
        $response = Http::post('https://app.pakasir.com/api/transactioncreate/qris', [
            'project'  => env('PAKASIR_SLUG', 'nokos-app'), 
            'order_id' => $paymentRef,
            'amount'   => $serverPrice, 
            'api_key'  => env('PAKASIR_API_KEY')
        ]);

        $result = $response->json();
        if ($response->successful() && isset($result['payment']['payment_number'])) {
            $order = Order::create([
                'user_id'       => auth()->id(),
                'service_code'  => $request->service_code,
                'country_id'    => $request->country_id,
                'api_version'   => $request->api_version,
                'selected_id'   => $request->selected_id,
                'price'         => $serverPrice,
                'payment_ref'   => $paymentRef,
                'payment_qr'    => $result['payment']['payment_number'],
                'total_payment' => $result['payment']['total_payment'],
                'status'        => 'pending_payment'
            ]);
            return redirect()->route('order.waiting', $order->id);
        }
        return back()->withErrors(['error' => 'Gagal membuat tagihan QRIS.']);
    }

    public function waiting(Order $order)
    {
        if ($order->user_id !== auth()->id()) abort(403);
        return Inertia::render('Order/Waiting', ['order' => $order]);
    }

    // UPDATE: FUNGSI CHECK OTP (DENGAN PENGECEKAN API_VERSION V4)
    public function checkOtp(Order $order)
    {
        if ($order->status === 'waiting_otp' && $order->activation_id) {
            
            // 🌟 TENTUKAN URL DAN API KEY BERDASARKAN SERVER YANG DIPAKAI
            $apiUrl = $order->api_version === 'v4' 
                ? "https://hero-sms.com/stubs/handler_api.php" 
                : "https://smsbower.page/stubs/handler_api.php";
                
            $apiKey = $order->api_version === 'v4' 
                ? env('HERO_OTP_API_KEY') 
                : env('SMSBOWER_API_KEY');

            $response = Http::get($apiUrl, [
                'api_key' => $apiKey,
                'action'  => 'getStatus',
                'id'      => $order->activation_id
            ]);

            $result = trim($response->body());

            if (strpos($result, 'STATUS_OK') !== false) {
                $parts = explode(':', $result);
                $otpCode = trim($parts[1] ?? '');
                $otpCode = str_replace(["'", '"'], '', $otpCode);

                if ($otpCode) {
                    $order->update(['otp_code' => $otpCode]);
                    return back()->with('success', 'SMS berhasil diterima!');
                }
            }

            return back()->withErrors(['otp' => 'Sistem sedang memantau SMS...']);
        }
        
        return back();
    }

public function cancel(Order $order, SmsBowerService $smsBower)
    {
        if ($order->user_id !== auth()->id()) abort(403);
        
        if (!in_array($order->status, ['waiting_otp', 'pending_payment'])) {
            return back()->withErrors(['error' => 'Order tidak bisa dibatalkan.']);
        }

        // 1. Cek di database lokal (jaga-jaga)
        if ($order->otp_code) {
            return back()->withErrors(['error' => 'OTP sudah diterima! Pesanan tidak bisa dibatalkan.']);
        }

        if ($order->activation_id) {
            
            // 🌟 TENTUKAN URL DAN API KEY
            $apiUrl = $order->api_version === 'v4' 
                ? "https://hero-sms.com/stubs/handler_api.php" 
                : "https://smsbower.page/stubs/handler_api.php";
                
            $apiKey = $order->api_version === 'v4' 
                ? env('HERO_OTP_API_KEY') 
                : env('SMSBOWER_API_KEY');

            // ==========================================
            // JURUS BARU: CEK KE PUSAT SEBELUM CANCEL! 
            // ==========================================
            $checkResponse = Http::get($apiUrl, [
                'api_key' => $apiKey,
                'action'  => 'getStatus',
                'id'      => $order->activation_id
            ]);

            $checkResult = trim($checkResponse->body());

            // Kalau ternyata OTP-nya UDAH MASUK di pusat detik ini juga:
            if (strpos($checkResult, 'STATUS_OK') !== false) {
                $parts = explode(':', $checkResult);
                $otpCode = trim($parts[1] ?? '');
                $otpCode = str_replace(["'", '"'], '', $otpCode);

                if ($otpCode) {
                    $order->update(['otp_code' => $otpCode]);
                    return back()->withErrors(['error' => 'Gagal dibatalkan! OTP baru saja masuk, silakan cek kode Anda.']);
                }
            }

            // ==========================================
            // KALAU AMAN (BENERAN KOSONG), BARU CANCEL KE PUSAT
            // ==========================================
            if ($order->api_version === 'v4') {
                // 🚀 KHUSUS V4: Pakai endpoint bawaan HeroOTP biar manjur
                $cancelResponse = Http::get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'cancelActivation',
                    'id'      => $order->activation_id
                ]);
                
                // CEK STRICT! Kalau API Hero nolak/error, JANGAN refund saldo!
                if (!$cancelResponse->successful()) {
                    return back()->withErrors(['error' => 'Server V4 menolak pembatalan (mungkin batas waktu belum habis). Coba lagi nanti.']);
                }
            } else {
                // 🚀 V1, V2, V3: Pakai setStatus = 8 (Standar SMS-Activate)
                $cancelResponse = Http::get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'setStatus',
                    'status'  => 8, 
                    'id'      => $order->activation_id
                ]);

                $cancelResult = trim($cancelResponse->body());

                if ($cancelResult === 'EARLY_CANCEL_DENIED') {
                    return back()->withErrors(['error' => 'Pembatalan gagal. Tunggu 2 menit setelah pembelian baru bisa dibatalkan.']);
                }
            }
        }

        // PROSES REFUND SALDO (Hanya jalan kalau API pusat berhasil ngebatalin)
        if ($order->status === 'waiting_otp') {
            DB::transaction(function () use ($order) {
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                $user->increment('balance', $order->price);
                $order->update(['status' => 'canceled']);
            });
        } else {
            $order->update(['status' => 'canceled']);
        }

        return redirect()->route('dashboard')->with('success', 'Order berhasil dibatalkan dan saldo dikembalikan.');
    }

    public function complete(Order $order)
    {
        if ($order->user_id !== auth()->id() || $order->status !== 'waiting_otp') abort(403);

        if ($order->activation_id) {
            $apiUrl = $order->api_version === 'v4' 
                ? "https://hero-sms.com/stubs/handler_api.php" 
                : "https://smsbower.page/stubs/handler_api.php";
                
            $apiKey = $order->api_version === 'v4' 
                ? env('HERO_OTP_API_KEY') 
                : env('SMSBOWER_API_KEY');

            if ($order->api_version === 'v4') {
                // 🚀 KHUSUS V4: Pakai endpoint finish
                Http::get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'finishActivation',
                    'id'      => $order->activation_id
                ]);
            } else {
                // 🚀 V1, V2, V3: Pakai setStatus = 6
                Http::get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'setStatus',
                    'status'  => 6,
                    'id'      => $order->activation_id
                ]);
            }
        }

        $order->update(['status' => 'completed']);
        return redirect()->route('dashboard')->with('success', 'Transaksi selesai!');
    }

    public function webhook(Request $request, SmsBowerService $smsBower)
    {
        Log::info('Webhook Pakasir:', $request->all());
        $paymentRef = $request->input('order_id');
        $status = $request->input('status'); 
        $project = $request->input('project');

        // CATATAN: Idealnya di sini ditambah pengecekan Signature dari Pakasir
        if ($project !== env('PAKASIR_SLUG')) return response()->json(['message' => 'Invalid Slug'], 400);

        $order = Order::where('payment_ref', $paymentRef)->first();

        if ($order && $order->status === 'pending_payment' && in_array(strtolower($status), ['success', 'paid', 'completed'])) {
            
            // 🌟 LOGIKA WEBHOOK DIBAGI BERDASARKAN SERVER
            if ($order->api_version === 'v4') {
                $v4Resp = Http::get('https://hero-sms.com/stubs/handler_api.php', [
                    'action'  => 'getNumberV2',
                    'service' => $order->service_code,
                    'country' => $order->country_id,
                    'api_key' => env('HERO_OTP_API_KEY')
                ]);
                $v4Data = $v4Resp->json();
                
                if (isset($v4Data['activationId'])) {
                    $apiResult = [
                        'success'       => true,
                        'activation_id' => $v4Data['activationId'],
                        'phone_number'  => $v4Data['phoneNumber']
                    ];
                } else {
                    $apiResult = ['success' => false];
                }
            } else {
                $extraParams = [];
                if ($order->api_version === 'v2') $extraParams['maxPrice'] = $order->selected_id; 
                elseif ($order->api_version === 'v3') $extraParams['providerIds'] = $order->selected_id; 

                $apiResult = $smsBower->getNumber($order->service_code, $order->country_id, $extraParams);
            }

            if ($apiResult['success']) {
                $order->update([
                    'activation_id' => $apiResult['activation_id'],
                    'phone_number'  => $apiResult['phone_number'],
                    'status'        => 'waiting_otp' 
                ]);
            } else {
                DB::transaction(function () use ($order) {
                    $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                    $user->increment('balance', $order->price);
                    $order->update(['status' => 'failed_api']);
                });
            }
            return response()->json(['message' => 'Success'], 200);
        }
        return response()->json(['message' => 'Data tidak diproses'], 400);
    }
}