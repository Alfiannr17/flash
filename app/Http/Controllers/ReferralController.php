<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class ReferralController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $totalInvited = User::where('referred_by', $userId)->count();

        $komisi = DB::table('top_ups')
            ->where('user_id', $userId)->where('reference', 'LIKE', 'KOMISI-%')
            ->select('reference', 'amount', 'status', 'created_at')
            ->get()->map(function($item) { $item->type = 'komisi'; return $item; });

        $converts = DB::table('top_ups')
            ->where('user_id', $userId)->where('reference', 'LIKE', 'CONVERT-%')
            ->select('reference', 'amount', 'status', 'created_at')
            ->get()->map(function($item) { $item->type = 'convert'; return $item; });

        $historyKomisi = $komisi->concat($converts)->sortByDesc('created_at')->values()->all();

        $historyWd = DB::table('withdrawals')
            ->where('user_id', $userId)
            ->select('reference', 'destination_number', 'amount', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()->map(function($item) { $item->type = 'withdraw'; return $item; })
            ->all();

        return Inertia::render('Referral/Index', [
            'totalInvited' => $totalInvited,
            'historyKomisi' => $historyKomisi,
            'historyWd' => $historyWd
        ]);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = User::where('id', auth()->id())->lockForUpdate()->first();

        if ($user->reff_balance < $request->amount) {
            return back()->withErrors(['reff_amount' => 'Saldo referral tidak mencukupi!']);
        }

        DB::transaction(function () use ($user, $request) {
            $user->decrement('reff_balance', $request->amount);
            $user->increment('balance', $request->amount);

            DB::table('top_ups')->insert([
                'user_id' => $user->id,
                'reference' => 'CONVERT-' . time(),
                'amount' => $request->amount,
                'status' => 'success',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return back()->with('success', 'Saldo Referral berhasil ditukar ke Saldo Utama!');
    }

    public function withdraw(Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|in:10000,20000,50000,100000',
        'dana_number' => 'required|string|min:10|max:15'
    ]);

    $user = auth()->user();
    $fee = 1000;
    $totalDeduction = $request->amount + $fee;
    
    $reference = 'WD-' . time() . '-' . $user->id;

    $userDb = User::where('id', $user->id)->lockForUpdate()->first();

    if ($userDb->reff_balance < $totalDeduction) {
        return back()->withErrors(['wd_amount' => "Saldo tidak cukup!"]);
    }

    DB::transaction(function () use ($user, $request, $fee, $totalDeduction, $reference) {
        $userDb = User::where('id', $user->id)->lockForUpdate()->first();
        $userDb->decrement('reff_balance', $totalDeduction);

        Withdrawal::create([
            'reference' => $reference, 
            'user_id' => $user->id,
            'amount' => $request->amount,
            'fee' => $fee,
            'destination_number' => $request->dana_number,
            'status' => 'pending'
        ]);
    });

    $botToken = env('TELEGRAM_BOT_TOKEN');
    $adminId = env('TELEGRAM_ADMIN_ID');

    $text = "*NEW WITHDRAWAL REQUEST*\n\n";
    $text .= "User: {$user->name}\n";
    $text .= "Invoice: `{$reference}`\n";
    $text .= "No DANA: `{$request->dana_number}`\n";
    $text .= "Nominal: *Rp " . number_format($request->amount, 0, ',', '.') . "*\n";
    $text .= "Status: *Pending*";

    Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
        'chat_id' => $adminId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'Approve', 'callback_data' => "wd_approve_{$reference}"],
                    ['text' => 'Reject', 'callback_data' => "wd_reject_{$reference}"]
                ]
            ]
        ])
    ]);

        return back()->with('success', 'Permintaan penarikan ke DANA berhasil dikirim. Mohon tunggu proses admin.');
    }
}