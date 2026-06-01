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

class OrderController extends Controller
{
    public function create(SmsBowerService $smsBower)
    {
        // ==========================================
        // CEK ORDER AKTIF
        // QRIS/PENDING_PAYMENT SUDAH TIDAK DIPAKAI
        // ==========================================
        $activeOrder = Order::where('user_id', auth()->id())
            ->whereIn('status', ['waiting_otp', 'creating_provider'])
            ->first();

        if ($activeOrder) {
            return redirect()->route('order.waiting', $activeOrder->id)
                ->withErrors([
                    'error' => 'Selesaikan atau batalkan pesanan aktif ini terlebih dahulu sebelum membuat pesanan baru!'
                ]);
        }

        $countries = [];
        $allServices = [];

        try {
            $countries = $smsBower->getCountries();
            $rawServices = $smsBower->getServicesList();

            if (is_array($rawServices)) {
                foreach ($rawServices as $code => $name) {
                    $allServices[] = [
                        'c' => (string) $code,
                        'n' => (string) $name
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('SMSBower Down: ' . $e->getMessage());
        }

        return Inertia::render('Order/Create', [
            'countries' => $countries,
            'allServices' => $allServices
        ]);
    }

    public function checkPrice(Request $request, SmsBowerService $smsBower)
    {
        $service = $request->service_code;
        $country = $request->country_id;
        $version = $request->api_version ?? 'v1';
        $options = [];

        $userBalance = auth()->user() ? auth()->user()->balance : 0;
        $isPromo = $userBalance <= 0;

        try {
            if ($version === 'v1') {
                $data = $smsBower->getPrices($service, $country);

                if (isset($data[$country][$service]['cost'])) {
                    $cost = (float) $data[$country][$service]['cost'];

                    $options[] = [
                        'id'    => 'default',
                        'name'  => 'Harga Rata-rata Server',
                        'price' => $this->calculateSellPrice($cost, $version, $isPromo),
                        'count' => $data[$country][$service]['count'] ?? 0,
                    ];
                }
            } elseif ($version === 'v2') {
                $data = $smsBower->getPricesV2($service, $country);

                if (isset($data[$country][$service]) && is_array($data[$country][$service])) {
                    foreach ($data[$country][$service] as $price => $count) {
                        $cost = (float) $price;

                        $options[] = [
                            'id'    => (string) $price,
                            'name'  => 'Server Stabil #' . rand(10, 99),
                            'price' => $this->calculateSellPrice($cost, $version, $isPromo),
                            'count' => $count,
                        ];
                    }
                }
            } elseif ($version === 'v3') {
                $data = $smsBower->getPricesV3($service, $country);

                if (isset($data[$country][$service]) && is_array($data[$country][$service])) {
                    foreach ($data[$country][$service] as $providerId => $info) {
                        if (isset($info['price'])) {
                            $cost = (float) $info['price'];

                            $options[] = [
                                'id'    => (string) $providerId,
                                'name'  => 'Provider ID: ' . $providerId,
                                'price' => $this->calculateSellPrice($cost, $version, $isPromo),
                                'count' => $info['count'] ?? 0,
                            ];
                        }
                    }
                }
            } elseif ($version === 'v4') {
                $response = Http::timeout(20)->get('https://hero-sms.com/stubs/handler_api.php', [
                    'action'  => 'getPrices',
                    'service' => $service,
                    'country' => $country,
                    'api_key' => config('services.hero_otp.api_key')
                ]);

                $data = $response->json();

                if (isset($data[$country][$service])) {
                    $info = $data[$country][$service];
                    $cost = (float) ($info['cost'] ?? 0);

                    if ($cost > 0) {
                        $options[] = [
                            'id'    => 'v4_' . $service . '_' . $country,
                            'name'  => 'Server Prime',
                            'price' => $this->calculateSellPrice($cost, $version, $isPromo),
                            'count' => $info['count'] ?? 0,
                        ];
                    }
                }
            }

            if (!empty($options)) {
                usort($options, fn ($a, $b) => $a['price'] <=> $b['price']);

                return response()->json([
                    'success' => true,
                    'options' => $options
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Check Price Error: ' . $e->getMessage());
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
            'api_version'    => 'required|string|in:v1,v2,v3,v4',
        ]);

        $activeOrder = Order::where('user_id', auth()->id())
            ->whereIn('status', ['waiting_otp', 'creating_provider'])
            ->first();

        if ($activeOrder) {
            return redirect()->route('order.waiting', $activeOrder->id)
                ->withErrors(['error' => 'Anda masih memiliki pesanan aktif!']);
        }

        try {
            $realPriceUsd = $this->getRealPriceUsd($request, $smsBower);
        } catch (\Exception $e) {
            Log::error('Get Real Price Error: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Layanan sedang gangguan, gagal mengambil harga. (' . $e->getMessage() . ')'
            ]);
        }

        if ($realPriceUsd <= 0) {
            return back()->withErrors([
                'error' => 'Layanan sedang gangguan, gagal mengambil harga.'
            ]);
        }

        $user = User::where('id', auth()->id())->first();
        $isPromo = $user && $user->balance <= 0;

        $serverPrice = $this->calculateSellPrice(
            $realPriceUsd,
            $request->api_version,
            $isPromo
        );

        $paymentRef = 'BAL-' . time() . '-' . auth()->id();

        DB::beginTransaction();

        try {
            $userLock = User::where('id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (!$userLock) {
                DB::rollBack();
                return back()->withErrors(['error' => 'User tidak ditemukan.']);
            }

            if ($userLock->balance < $serverPrice) {
                DB::rollBack();
                return back()->withErrors([
                    'error' => 'Saldo tidak cukup! Harga: Rp ' . number_format($serverPrice, 0, ',', '.')
                ]);
            }

            $userLock->decrement('balance', $serverPrice);

            $order = Order::create([
                'user_id'       => $userLock->id,
                'service_code'  => $request->service_code,
                'api_version'   => $request->api_version,
                'country_id'    => $request->country_id,
                'selected_id'   => $request->selected_id,
                'price'         => $serverPrice,
                'payment_ref'   => $paymentRef,
                'activation_id' => null,
                'phone_number'  => null,
                'status'        => 'creating_provider'
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create Local Order Error: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Terjadi kesalahan sistem. Saldo aman.'
            ]);
        }

        $apiResult = null;

        try {
            $apiResult = $this->buyNumberFromProvider($request, $realPriceUsd, $smsBower);

            if (!is_array($apiResult) || empty($apiResult['success'])) {
                throw new \Exception($apiResult['error'] ?? 'Server sibuk');
            }

            $activationId = $apiResult['activation_id'] ?? null;
            $phoneNumber = $apiResult['phone_number'] ?? null;

            if (!$activationId || !$phoneNumber) {
                throw new \Exception('Provider tidak mengirim activation ID atau nomor HP.');
            }

            $order->update([
                'activation_id' => $activationId,
                'phone_number'  => $phoneNumber,
                'status'        => 'waiting_otp'
            ]);

            $this->sendOrderTelegram($order);

            return redirect()->route('order.waiting', $order->id);
        } catch (\Exception $e) {
            Log::error('Provider Order Error: ' . $e->getMessage());

            if (is_array($apiResult) && !empty($apiResult['activation_id'])) {
                $this->cancelProviderActivation(
                    $request->api_version,
                    $apiResult['activation_id']
                );
            }

            DB::transaction(function () use ($order) {
                $freshOrder = Order::where('id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$freshOrder) return;

                $user = User::where('id', $freshOrder->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($user && in_array($freshOrder->status, ['creating_provider'])) {
                    $user->increment('balance', $freshOrder->price);

                    $freshOrder->update([
                        'status' => 'failed_api'
                    ]);
                }
            });

            return back()->withErrors([
                'error' => 'Gagal mengambil nomor: ' . $e->getMessage()
            ]);
        }
    }

    public function waiting(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Order/Waiting', [
            'order' => $order
        ]);
    }

    public function checkOtp(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->status === 'waiting_otp' && $order->activation_id) {
            [$apiUrl, $apiKey] = $this->providerConfig($order->api_version);

            if ($order->api_version === 'v4') {
                $v4Response = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'getStatusV2',
                    'id'      => $order->activation_id
                ]);

                $data = $v4Response->json();

                $otpCode = null;

                if (isset($data['sms']['code']) && !empty($data['sms']['code'])) {
                    $otpCode = $data['sms']['code'];
                } elseif (isset($data['call']['code']) && !empty($data['call']['code'])) {
                    $otpCode = $data['call']['code'];
                }

                if ($otpCode) {
                    $order->update(['otp_code' => $otpCode]);
                    return back()->with('success', 'SMS berhasil diterima!');
                }

                $textResponse = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'getStatus',
                    'id'      => $order->activation_id
                ]);

                $result = trim($textResponse->body());

                if (strpos($result, 'STATUS_OK') !== false) {
                    $parts = explode(':', $result);
                    $otpTextCode = trim($parts[1] ?? '');
                    $otpTextCode = str_replace(["'", '"'], '', $otpTextCode);

                    if ($otpTextCode) {
                        $order->update(['otp_code' => $otpTextCode]);
                        return back()->with('success', 'SMS berhasil diterima!');
                    }
                }

                return back()->withErrors(['otp' => 'Sistem sedang memantau SMS...']);
            }

            $response = Http::timeout(20)->get($apiUrl, [
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
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($order->status, ['waiting_otp', 'pending_payment', 'creating_provider'])) {
            return back()->withErrors([
                'error' => 'Order tidak bisa dibatalkan.'
            ]);
        }

        // ==========================================
        // HANDLER KHUSUS ORDER STUCK DI CREATING_PROVIDER
        // ==========================================
        if ($order->status === 'creating_provider') {
            DB::transaction(function () use ($order) {
                $freshOrder = Order::where('id', $order->id)->lockForUpdate()->first();
                
                if ($freshOrder && $freshOrder->status === 'creating_provider') {
                    User::where('id', $freshOrder->user_id)
                        ->lockForUpdate()
                        ->first()
                        ->increment('balance', $freshOrder->price);
                        
                    $freshOrder->update(['status' => 'canceled']);
                }
            });
            return redirect()->route('dashboard')->with('success', 'Order stuck berhasil dibatalkan dan saldo dikembalikan.');
        }

        if ($order->otp_code) {
            return back()->withErrors([
                'error' => 'OTP sudah diterima! Pesanan tidak bisa dibatalkan.'
            ]);
        }

        if ($order->status === 'waiting_otp' && $order->activation_id) {
            [$apiUrl, $apiKey] = $this->providerConfig($order->api_version);

            if ($order->api_version === 'v4') {
                $v4Check = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'getStatusV2',
                    'id'      => $order->activation_id
                ]);

                $dataV4 = $v4Check->json();
                $otpCode = null;

                if (isset($dataV4['sms']['code']) && !empty($dataV4['sms']['code'])) {
                    $otpCode = $dataV4['sms']['code'];
                } elseif (isset($dataV4['call']['code']) && !empty($dataV4['call']['code'])) {
                    $otpCode = $dataV4['call']['code'];
                }

                if ($otpCode) {
                    $order->update(['otp_code' => $otpCode]);
                    return back()->withErrors(['error' => 'Gagal dibatalkan! OTP sudah masuk, silakan cek kode Anda.']);
                }

                $textCheck = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'getStatus',
                    'id'      => $order->activation_id
                ]);

                $textResult = trim($textCheck->body());

                if (strpos($textResult, 'STATUS_OK') !== false) {
                    $parts = explode(':', $textResult);
                    $otpTextCode = trim($parts[1] ?? '');
                    $otpTextCode = str_replace(["'", '"'], '', $otpTextCode);

                    if ($otpTextCode) {
                        $order->update(['otp_code' => $otpTextCode]);
                        return back()->withErrors(['error' => 'Gagal dibatalkan! OTP sudah masuk, silakan cek kode Anda.']);
                    }
                }

                $cancelResponse = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'cancelActivation',
                    'id'      => $order->activation_id
                ]);

                if (!$cancelResponse->successful() || strpos($cancelResponse->body(), 'CANT_CANCEL') !== false) {
                    return back()->withErrors(['error' => 'Server V4 menolak pembatalan. Coba lagi nanti.']);
                }
            } else {
                $checkResponse = Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'getStatus',
                    'id'      => $order->activation_id
                ]);

                $checkResult = trim($checkResponse->body());

                if (strpos($checkResult, 'STATUS_OK') !== false) {
                    $parts = explode(':', $checkResult);
                    $otpCode = trim($parts[1] ?? '');
                    $otpCode = str_replace(["'", '"'], '', $otpCode);

                    if ($otpCode) {
                        $order->update(['otp_code' => $otpCode]);
                        return back()->withErrors(['error' => 'Gagal dibatalkan! OTP baru saja masuk, silakan cek kode Anda.']);
                    }
                }

                $cancelResponse = Http::timeout(20)->get($apiUrl, [
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

        if ($order->status === 'waiting_otp') {
            DB::transaction(function () use ($order) {
                $freshOrder = Order::where('id', $order->id)->lockForUpdate()->first();

                if (!$freshOrder || $freshOrder->status !== 'waiting_otp') return;

                $user = User::where('id', $freshOrder->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->increment('balance', $freshOrder->price);
                }

                $freshOrder->update(['status' => 'canceled']);
            });
        } else {
            $order->update(['status' => 'canceled']);
        }

        return redirect()->route('dashboard')->with('success', 'Order berhasil dibatalkan dan saldo dikembalikan.');
    }

    public function complete(Order $order)
    {
        if ($order->user_id !== auth()->id() || $order->status !== 'waiting_otp') {
            abort(403);
        }

        if ($order->activation_id) {
            [$apiUrl, $apiKey] = $this->providerConfig($order->api_version);

            if ($order->api_version === 'v4') {
                Http::timeout(20)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'finishActivation',
                    'id'      => $order->activation_id
                ]);
            } else {
                Http::timeout(20)->get($apiUrl, [
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
        Log::info('Webhook Pakasir ditolak karena QRIS sudah dinonaktifkan:', $request->all());

        return response()->json([
            'message' => 'Webhook QRIS/Pakasir sudah dinonaktifkan. Order hanya menggunakan saldo akun.'
        ], 410);
    }

    private function getRealPriceUsd(Request $request, SmsBowerService $smsBower): float
    {
        if ($request->api_version === 'v1') {
            $data = $smsBower->getPrices($request->service_code, $request->country_id);
            return (float) ($data[$request->country_id][$request->service_code]['cost'] ?? 0);
        }

        // ==========================================
        // BUG FIX 1: VALIDASI HARGA V2
        // ==========================================
        if ($request->api_version === 'v2') {
            $data = $smsBower->getPricesV2($request->service_code, $request->country_id);
            $prices = array_keys($data[$request->country_id][$request->service_code] ?? []);
            
            if (!in_array((string)$request->selected_id, array_map('strval', $prices), true)) {
                throw new \Exception('Harga V2 tidak valid atau sudah berubah dari provider.');
            }
            return (float) $request->selected_id;
        }

        if ($request->api_version === 'v3') {
            $data = $smsBower->getPricesV3($request->service_code, $request->country_id);
            return (float) ($data[$request->country_id][$request->service_code][$request->selected_id]['price'] ?? 0);
        }

        if ($request->api_version === 'v4') {
            $response = Http::timeout(20)->get('https://hero-sms.com/stubs/handler_api.php', [
                'action'  => 'getPrices',
                'service' => $request->service_code,
                'country' => $request->country_id,
                'api_key' => config('services.hero_otp.api_key')
            ]);
            $data = $response->json();
            return (float) ($data[$request->country_id][$request->service_code]['cost'] ?? 0);
        }

        return 0;
    }

    private function calculateSellPrice(float $realPriceUsd, string $apiVersion, bool $isPromo = false): int
    {
        $usdRate = config('services.smsbower.usd_rate', env('SMSBOWER_USD_RATE', 17300));
        $profitMargin = config('services.smsbower.profit_margin', env('NOKOS_PROFIT_MARGIN', 2000));

        if ($apiVersion === 'v4') {
            $price = (int) ceil(($realPriceUsd * 17300) + 2127);
        } else {
            $price = (int) round(($realPriceUsd * $usdRate) + $profitMargin);
        }

        if ($isPromo) {
            $price = (int) round($price * 0.8);
        }

        return max($price, 0);
    }

    private function buyNumberFromProvider(Request $request, float $realPriceUsd, SmsBowerService $smsBower): array
    {
        if ($request->api_version === 'v4') {
            $v4Response = Http::timeout(25)->get('https://hero-sms.com/stubs/handler_api.php', [
                'action'   => 'getNumberV2',
                'service'  => $request->service_code,
                'country'  => $request->country_id,
                'maxPrice' => $realPriceUsd,
                'api_key'  => config('services.hero_otp.api_key')
            ]);

            $v4Data = $v4Response->json();

            if (isset($v4Data['activationId'])) {
                return [
                    'success'       => true,
                    'activation_id' => $v4Data['activationId'],
                    'phone_number'  => $v4Data['phoneNumber'] ?? null
                ];
            }

            return [
                'success' => false,
                'error'   => $v4Data['error'] ?? 'Stok HeroOTP kosong/gangguan'
            ];
        }

        $extraParams = [];

        if ($request->api_version === 'v1') {
            $extraParams['maxPrice'] = $realPriceUsd;
        } elseif ($request->api_version === 'v2') {
            $extraParams['maxPrice'] = $request->selected_id;
        } elseif ($request->api_version === 'v3') {
            $extraParams['providerIds'] = $request->selected_id;
        }

        $apiResult = $smsBower->getNumber(
            $request->service_code,
            $request->country_id,
            $extraParams
        );

        if (!is_array($apiResult)) {
            return [
                'success' => false,
                'error'   => 'Response provider tidak valid'
            ];
        }

        return $apiResult;
    }

    private function cancelProviderActivation(string $apiVersion, $activationId): void
    {
        try {
            [$apiUrl, $apiKey] = $this->providerConfig($apiVersion);

            if ($apiVersion === 'v4') {
                Http::timeout(15)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'cancelActivation',
                    'id'      => $activationId
                ]);
            } else {
                Http::timeout(15)->get($apiUrl, [
                    'api_key' => $apiKey,
                    'action'  => 'setStatus',
                    'status'  => 8,
                    'id'      => $activationId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Cancel Provider Activation Error: ' . $e->getMessage());
        }
    }

    private function providerConfig(string $apiVersion): array
    {
        if ($apiVersion === 'v4') {
            return [
                'https://hero-sms.com/stubs/handler_api.php',
                config('services.hero_otp.api_key')
            ];
        }

        return [
            'https://smsbower.page/stubs/handler_api.php',
            config('services.smsbower.api_key')
        ];
    }

    private function sendOrderTelegram(Order $order): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (!$botToken || !$chatId) {
            return;
        }

        try {
            $pesanWeb  = "<b>ORDER BARU FLASHOTP</b>\n";
            $pesanWeb .= "━━━━━━━━━━━━━━━━━\n";
            $pesanWeb .= "User ID: " . $this->tg($order->user_id) . "\n";
            $pesanWeb .= "ID Order: " . $this->tg($order->activation_id) . "\n";
            $pesanWeb .= "Layanan: " . $this->tg($order->service_code) . " (" . $this->tg($order->api_version) . ")\n";
            $pesanWeb .= "Nomor: " . $this->tg($order->phone_number) . "\n";
            $pesanWeb .= "Harga: Rp " . number_format($order->price, 0, ',', '.') . "\n";
            $pesanWeb .= "Metode: SALDO AKUN";

            Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $pesanWeb,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram Order Notify Error: ' . $e->getMessage());
        }
    }

    private function tg($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}