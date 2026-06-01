<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    private $botToken;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN'); 
    }

    public function handle(Request $request)
    {
        $update = $request->all();

        // 1. ADMIN KLIK TOMBOL "HUBUNGKAN" (Callback Query)
        if (isset($update['callback_query'])) {
            $callbackId = $update['callback_query']['id'];
            $adminTeleId = $update['callback_query']['from']['id'];
            $data = $update['callback_query']['data']; 

            if (str_starts_with($data, 'connect_')) {
                $sessionId = explode('_', $data)[1];
                $session = ChatSession::find($sessionId);

                if ($session && $session->status === 'waiting') {
                    
                    // 🌟 OBAT NYANGKUT 1: Tutup paksa semua sesi lama admin ini biar ga bentrok!
                    ChatSession::where('telegram_admin_id', $adminTeleId)
                               ->where('status', 'active')
                               ->update(['status' => 'closed']);

                    // Baru aktifin sesi yang baru aja di-klik
                    $session->update([
                        'status' => 'active',
                        'telegram_admin_id' => $adminTeleId
                    ]);

                    $this->sendMessage($adminTeleId, "✅ Berhasil terhubung dengan User ID {$session->user_id}. Silakan ketik balasan Anda di sini. Ketik /end untuk mengakhiri sesi.");
                } else {
                    $this->sendMessage($adminTeleId, "❌ Sesi chat ini sudah diambil admin lain atau sudah ditutup.");
                }

                Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
                    'callback_query_id' => $callbackId
                ]);
            }
            return response('ok', 200);
        }

        // 2. ADMIN NGETIK BALASAN DI TELEGRAM (Message)
        if (isset($update['message'])) {
            $adminTeleId = $update['message']['from']['id'];
            $text = $update['message']['text'] ?? '';

            // 🌟 OBAT NYANGKUT 2: Cari sesi aktif yang PALING BARU (latest)
            $activeSession = ChatSession::where('telegram_admin_id', $adminTeleId)
                                        ->where('status', 'active')
                                        ->latest('id')
                                        ->first();

            if ($activeSession) {
                if ($text === '/end') {
                    $activeSession->update(['status' => 'closed']);
                    $this->sendMessage($adminTeleId, "🛑 Sesi obrolan telah diakhiri.");
                } else {
                    // Simpan balasan Admin ke database
                    ChatMessage::create([
                        'chat_session_id' => $activeSession->id,
                        'sender_type' => 'admin',
                        'message' => $text
                    ]);
                }
            }
        }

        return response('ok', 200);
    }

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}