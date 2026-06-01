<?php

namespace App\Http\Controllers;

use App\Models\TopUp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // WAJIB DITAMBAHIN UNTUK KEAMANAN
use Inertia\Inertia;

class TopUpController extends Controller
{
    public function create()
    {
        
        $hasReceivedBonus = DB::table('top_ups')
            ->where('user_id', auth()->id())
            ->where('reference', 'like', 'BONUS-%')
            ->where('status', 'success')
            ->exists();

        return Inertia::render('TopUp/Create', [
            'balance' => (float) auth()->user()->balance,
            'hasReceivedBonus' => $hasReceivedBonus 
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5000'
        ]);

        $user = auth()->user();
        $reference = 'TOPUP-' . time() . '-' . $user->id;

        try {
            // Gunakan config() agar aman saat php artisan config:cache di server production
            $apiUrl = config('services.pakasir.url', env('PAKASIR_API_URL', 'https://app.pakasir.com/api/transactioncreate/qris'));
            $apiKey = config('services.pakasir.api_key', env('PAKASIR_API_KEY'));
            $projectSlug = config('services.pakasir.slug', env('PAKASIR_SLUG'));

            $response = Http::post($apiUrl, [
                'project'  => $projectSlug,
                'order_id' => $reference,
                'amount'   => (int) $request->amount,
                'api_key'  => $apiKey
            ]);

            $result = $response->json();

            // KODE DIRAPIKAN: Hapus if bersarang yang dobel
            if ($response->successful() && isset($result['payment']['payment_number'])) {
                $qrisPayload = $result['payment']['payment_number'];
                // Optional: kalau lu butuh nyimpen total_payment dari Pakasir, bisa ditambah ke DB lu
                // $totalPayment = $result['payment']['total_payment']; 

                TopUp::create([
                    'user_id'      => $user->id,
                    'amount'       => $request->amount, 
                    'reference'    => $reference,
                    'checkout_url' => $qrisPayload, 
                    'status'       => 'pending'
                ]);

                return redirect()->route('topup.show', $reference);
            }

            Log::error('Pakasir Gagal Bikin QRIS: ', ['response' => $result]);
            return back()->withErrors(['api' => 'Gagal membuat tagihan QRIS. Coba beberapa saat lagi.']);

        } catch (\Exception $e) {
            Log::error('Pakasir Koneksi Error: ' . $e->getMessage());
            return back()->withErrors(['api' => 'Sistem pembayaran sedang gangguan. Coba lagi nanti.']);
        }
    }

    public function show($reference)
    {
        $topUp = TopUp::where('reference', $reference)
                      ->where('user_id', auth()->id())
                      ->firstOrFail();

        return Inertia::render('TopUp/Show', [
            'topUp' => $topUp
        ]);
    }

    // FUNGSI WEBHOOK YANG SUDAH DIBUAT KEBAL HACKER & DOUBLE-SPEND
    public function webhook(Request $request)
    {
        Log::info('--- WEBHOOK PAKASIR MASUK (TOPUP) ---', $request->all());

        $reference = $request->input('order_id'); 
        $status = $request->input('status');
        $project = $request->input('project');

        // KEAMANAN 1: Pastikan request beneran datang untuk project lu, bukan project orang lain
        $validSlug = config('services.pakasir.slug', env('PAKASIR_SLUG'));
        if ($project !== $validSlug) {
            Log::critical("SERANGAN WEBHOOK: Slug Project Tidak Valid! Target: $reference");
            return response()->json(['message' => 'Invalid Request'], 403);
        }

        // Pastikan status yang dikirim adalah status sukses
        if (!in_array(strtolower($status), ['success', 'paid', 'settled', 'completed'])) {
            return response()->json(['success' => false, 'message' => 'Status belum dibayar'], 200);
        }

        try {
            // KEAMANAN 2: DB Transaction & Lock (Anti Double-TopUp)
            DB::beginTransaction();

            // Cari transaksi TopUp dan KUNCI barisnya biar nggak diproses 2x secara bersamaan
            $topUp = TopUp::where('reference', $reference)->lockForUpdate()->first();

            // Kalau top-up nggak ketemu atau statusnya UDAH nggak pending (artinya udah masuk duluan)
            if (!$topUp || $topUp->status !== 'pending') {
                DB::rollBack();
                Log::warning("WEBHOOK DIABAIKAN: Reference {$reference} sudah diproses atau tidak ditemukan.");
                return response()->json(['success' => false, 'message' => 'Sudah diproses'], 200);
            }

            // 1. Update status transaksi jadi sukses
            $topUp->update(['status' => 'success']);

            // 2. Cari user dan KUNCI baris saldonya
            $user = User::where('id', $topUp->user_id)->lockForUpdate()->first();
            
            // ====================================================
            // ðŸŒŸ LOGIKA TAMBAHAN: CEK & BERIKAN BONUS EVENT TOP UP (LIMIT 1X KLAIM)
            // ====================================================
            $hasReceivedBonus = DB::table('top_ups')
                ->where('user_id', $user->id)
                ->where('reference', 'like', 'BONUS-%')
                ->where('status', 'success')
                ->exists();

            $bonus = 0;
            // Hanya berikan bonus JIKA belum pernah dapat sama sekali
            if (!$hasReceivedBonus) {
                if ($topUp->amount == 50000) {
                    $bonus = 5000;
                } elseif ($topUp->amount == 100000) {
                    $bonus = 10000;
                }
            }

            // Tambahkan saldo asli digabung dengan bonusnya
            $user->increment('balance', $topUp->amount + $bonus);

            // Jika dia dapat bonus, catat di history riwayat top_ups biar usernya tau itu saldo darimana
            if ($bonus > 0) {
                DB::table('top_ups')->insert([
                    'user_id' => $user->id,
                    'reference' => 'BONUS-' . time() . '-' . $user->id,
                    'amount' => $bonus,
                    'status' => 'success',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info("BONUS DIBERIKAN: User ID {$user->id} dapat bonus Rp {$bonus}");
            }
            // ====================================================

            // ====================================================
            // JURUS KOMISI 3% REFERRAL (SUDAH DIPINDAHKAN KE SINI)
            // ====================================================
            if ($user && $user->referred_by != null) {
                // Cari bosnya (Upline) dan kunci datanya biar aman
                $upline = User::where('id', $user->referred_by)->lockForUpdate()->first();
                
                if ($upline) {
                    // Hitung persentase random antara 3 sampai 7
                    $persentaseGacha = rand(3, 7) / 100;
                    
                    // Kalikan nominal top up dengan persentase random tersebut
                    $komisi = $topUp->amount * $persentaseGacha; 
                    
                    // Tambah saldo Upline
                    // Mulai sekarang komisi masuk ke saldo referral (reff_balance)
                    $upline->increment('reff_balance', $komisi);

                    // Catat komisi ke riwayat tabel top_ups biar muncul di Dashboard Upline
                    DB::table('top_ups')->insert([
                        'user_id' => $upline->id,
                        'reference' => 'KOMISI-' . time() . '-' . $user->id, 
                        'amount' => $komisi,
                        'status' => 'success',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    Log::info("KOMISI REFERRAL SUKSES: Upline ID {$upline->id} dapat Rp {$komisi}");
                }
            }
            // ====================================================

            DB::commit(); // SIMPAN PERMANEN! SEMUA PROSES (TOPUP & KOMISI) BERHASIL.

            Log::info("WEBHOOK SUKSES: Saldo Rp {$topUp->amount} (+ Bonus Rp {$bonus}) ditambahkan ke User ID {$user->id}");
            return response()->json(['success' => true, 'message' => 'Saldo masuk'], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Kalau ada gagal di tengah jalan, batalkan semuanya!
            Log::error("ERROR WEBHOOK TOPUP: " . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function cancel(TopUp $topUp)
    {
        if ($topUp->user_id !== auth()->id() || $topUp->status !== 'pending') {
            abort(403);
        }

        $topUp->update(['status' => 'failed']);

        return redirect()->route('topup.create')->with('success', 'Pembayaran Top Up berhasil dibatalkan.');
    }
}