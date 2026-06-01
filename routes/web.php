<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TelegramWebhookController;

// Models
use App\Models\Order;
use App\Models\TopUp;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/terms', function () {
    return Inertia::render('Legal/Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Legal/Privacy');
})->name('privacy');


/*
|--------------------------------------------------------------------------
| Webhook Routes (Harus di luar Auth)
|--------------------------------------------------------------------------
*/

Route::post('/webhook/pakasir', [TopUpController::class, 'webhook'])->name('webhook.pakasir');
Route::post('/webhook/pakasir/order', [OrderController::class, 'webhook'])->name('webhook.pakasir.order');
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle']);


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard & FOMO Logic
    Route::get('/dashboard', function () {
        $userId = auth()->id();
        
        $banners = DB::table('banners')->where('is_active', 1)->orderBy('id', 'desc')->get();

        $latestOrders = Order::with('user')->where('status', 'completed')->orderBy('created_at', 'desc')->take(25)->get();
        $latestTopUps = TopUp::with('user')->where('status', 'success')->orderBy('created_at', 'desc')->take(25)->get();

        $liveNotifs = collect();

        foreach($latestOrders as $o) {
            $name = $o->user ? mb_strtoupper(mb_substr($o->user->name, 0, 2, 'UTF-8'), 'UTF-8') . '***' : 'GU***';
            $otp = $o->otp_code ? mb_substr($o->otp_code, 0, 2, 'UTF-8') . '***' : '***';
            $liveNotifs->push([
                'type' => 'order',
                'message' => "{$name} Berhasil order nokos {$o->service_code} (OTP: {$otp})",
                'time' => 'Baru saja'
            ]);
        }

        foreach($latestTopUps as $t) {
            $name = $t->user ? mb_strtoupper(mb_substr($t->user->name, 0, 3, 'UTF-8'), 'UTF-8') . '***' : 'GU***';
            $amount = number_format($t->amount, 0, ',', '.');

            $msgText = match (true) {
                str_starts_with($t->reference, 'KOMISI') => "{$name} Mendapatkan Komisi Referral Rp {$amount}",
                str_starts_with($t->reference, 'TOPUP') => "{$name} Berhasil TopUp Saldo Rp {$amount}",
                str_starts_with($t->reference, 'CONVERT') => "{$name} Berhasil Convert Saldo Rp {$amount}",
                default => "{$name} Melakukan Transaksi Rp {$amount}",
            };

            $liveNotifs->push([
                'type' => 'topup',
                'message' => $msgText,
                'time' => 'Baru saja'
            ]);
        }

        return Inertia::render('Dashboard', [
            'recentOrders' => Order::where('user_id', $userId)->orderBy('created_at', 'desc')->take(5)->get(),
            'recentTopUps' => TopUp::where('user_id', $userId)->orderBy('created_at', 'desc')->take(5)->get(),
            'totalOrderCount' => Order::where('user_id', $userId)->count(),
            'totalTopUpCount' => TopUp::where('user_id', $userId)->where('status', 'success')->count(),
            'banners' => $banners,
            'liveNotifs' => $liveNotifs->shuffle()->values(),
        ]);
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Order Nokos
    Route::get('/order', [OrderController::class, 'create'])->name('order.create');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::get('/order/{order}/waiting', [OrderController::class, 'waiting'])->name('order.waiting');
    Route::get('/order/{order}/check-otp', [OrderController::class, 'checkOtp'])->name('order.check-otp');
    Route::post('/order/check-price', [OrderController::class, 'checkPrice'])->name('order.check-price');
    Route::post('/order/{order}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
    Route::post('/order/{order}/complete', [OrderController::class, 'complete'])->name('order.complete');
    
    // Top Up
    Route::get('/topup', [TopUpController::class, 'create'])->name('topup.create');
    Route::post('/topup', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/topup/invoice/{reference}', [TopUpController::class, 'show'])->name('topup.show');

    // Referral System
    Route::get('/referral', [ReferralController::class, 'index'])->name('referral.index');
    Route::post('/referral/convert', [ReferralController::class, 'convert'])->name('referral.convert');
    Route::post('/referral/withdraw', [ReferralController::class, 'withdraw'])->name('referral.withdraw');

    // Pricing list
    Route::get('/prices', [PriceController::class, 'index'])->name('prices.index');

    // Chat System
    Route::post('/chat/request', [ChatController::class, 'requestConnect']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/pull/{sessionId}', [ChatController::class, 'pullMessages']);
    Route::post('/chat/report-saldo', [ChatController::class, 'reportSaldo']);

});

/*
|--------------------------------------------------------------------------
| Utilities / Maintenance Routes
|--------------------------------------------------------------------------
*/

Route::post('/email/update-unverified', function () {
    request()->validate([
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . request()->user()->id],
    ], [
        'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
    ]);

    $user = request()->user();
    $user->email = request()->email;
    $user->email_verified_at = null; 
    $user->save();

    $user->sendEmailVerificationNotification();

    return Redirect::route('verification.notice')->with('status', 'email-updated');
})->middleware('auth','throttle:3,1')->name('email.update.unverified');


// Auth routes bawaan Laravel Breeze
require __DIR__.'/auth.php';