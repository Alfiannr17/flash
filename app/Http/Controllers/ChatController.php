<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private $botToken;
    private $adminId;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        // Sekarang ngambil ID Personal lu dari .env
        $this->adminId = env('TELEGRAM_ADMIN_ID'); 
    }

    // 1. User pencet "Hubungi Admin"
    public function requestConnect()
    {
        $user = auth()->user();
        
        $session = ChatSession::create([
            'user_id' => $user->id,
            'status' => 'waiting'
        ]);

        // Notif LANGSUNG DIKIRIM KE DM BOT LU
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $this->adminId,
            'text' => "ðŸ”” <b>MINTA BANTUAN!</b>\n\nUser: {$user->name}\nEmail: {$user->email}\n\nAda yang mau ngobrol nih bos, tolong direspon ya!",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => 'âš¡ HUBUNGKAN SAYA', 'callback_data' => "connect_{$session->id}"]
                    ]
                ]
            ])
        ]);

        return response()->json(['session_id' => $session->id]);
    }

    // 2. User ngirim pesan dari Website
    public function sendMessage(Request $request)
    {
        $session = ChatSession::find($request->session_id);
        
        if (!$session || $session->status === 'closed') return response()->json(['error' => 'Sesi ditutup'], 400);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'user',
            'message' => $request->message
        ]);

        // Tembak pesannya langsung ke DM bot Tele lu
        if ($session->status === 'active' && $session->telegram_admin_id) {
            Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $session->telegram_admin_id,
                'text' => "ðŸ‘¤ User: " . $request->message
            ]);
        }

        return response()->json(['success' => true]);
    }

    // 3. Polling Vue narik pesan baru & ngecek status
    public function pullMessages($sessionId)
    {
        $session = ChatSession::find($sessionId);
        $messages = ChatMessage::where('chat_session_id', $sessionId)->get();

        return response()->json([
            'status' => $session->status, // waiting, active, closed
            'messages' => $messages
        ]);
    }

    public function reportSaldo(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'email' => 'required|email',
            'no_invoice' => 'required|string',
        ]);

        $message = "ðŸš¨ *LAPORAN SALDO BELUM MASUK* ðŸš¨\n\n";
        $message .= "ðŸ‘¤ *Nama:* " . $request->nama . "\n";
        $message .= "ðŸ“§ *Email:* " . $request->email . "\n";
        $message .= "ðŸ§¾ *No. Invoice:* `" . $request->no_invoice . "`\n\n";
        $message .= "_Tolong segera dicek bos!_";

        // Ganti pakai variabel .env bot lu (Sesuaikan dengan setup Telegram yang lu pake)
        $botToken = env('TELEGRAM_BOT_TOKEN'); 
        $adminChatId = env('TELEGRAM_ADMIN_ID'); 

        if ($botToken && $adminChatId) {
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $adminChatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);
        }

        return response()->json(['success' => true]);
    }
}