<script setup>
import { ref, nextTick, onUnmounted } from 'vue';
import axios from 'axios';

// ==========================================
// 🌟 STATE UNTUK BOT & TELEGRAM CHAT
// ==========================================
const isOpen = ref(false);
const chatContainer = ref(null);

// MENGATUR STATUS PERCAKAPAN
const botState = ref('MAIN_MENU');

// State khusus untuk integrasi Telegram
const chatSessionId = ref(null);
const telegramStatus = ref(''); // 'waiting', 'active', 'closed'
const inputMessage = ref('');
let chatPollInterval = null;

// State khusus untuk Laporan Saldo
const currentFaqContext = ref('');
const saldoForm = ref({ nama: '', email: '', no_invoice: '' });

const messages = ref([
    { sender: 'bot', text: 'Halo bosku! Ada yang bisa FlashBot bantu? Pilih pertanyaan di bawah ya!' }
]);

const faqs = [
    { 
        q: "Berapa lama OTP masuk?", 
        a: "Biasanya kode OTP masuk sekitar ±2 menit, tapi kadang bisa lebih cepat atau sedikit lebih lama ya kak.<br><br>Kalau pakai WhatsApp MOD (tidak resmi) atau clone app (bukan bawaan sistem), memang OTP sering agak lama masuknya.<br><br>Saya juga sudah coba langsung pakai salah satu server dan daftar lewat aplikasi WhatsApp resmi, hasilnya OTP bisa masuk cepat tanpa kendala.<br><br>Kalau dirasa terlalu lama dan tidak sabar menunggu, bisa dibatalkan saja lalu order ulang ya kak." 
    },
    { 
        q: "Gimana cara order nomor?", 
        a: "Gampang banget bos!<br>1. Pastikan saldo cukup.<br>2. Di menu Order, pilih Layanan (misal WA) & Negara.<br>3. Klik 'Beli Nomor'.<br>4. Tunggu di halaman Invoice sampai muncul tulisan <b>OTP Diterima</b>." 
    },
    { 
        q: "Cara Top Up saldo?", 
        a: "1. Ke menu <b>Dashboard</b>.<br>2. Klik tombol biru <b>+ TOPUP</b>.<br>3. Masukkan nominal.<br>4. Scan QRIS yang muncul pakai M-Banking / e-Wallet.<br>5. Saldo otomatis masuk hitungan detik!" 
    },
    {
        q: "Saldo Top Up belum masuk?",
        a: "Biasanya saldo Top Up masuk secara instan hitungan detik ya bosku!<br><br>Namun kadang dari pihak QRIS/Bank sedang ada delay dan butuh waktu 1-2 menit untuk memproses."
    }
];

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

// 1. SAAT USER PILIH PERTANYAAN
const askQuestion = (faq) => {
    currentFaqContext.value = faq.q; 
    messages.value.push({ sender: 'user', text: faq.q });
    botState.value = 'TYPING';
    scrollToBottom();

    setTimeout(() => {
        messages.value.push({ sender: 'bot', text: faq.a });
        scrollToBottom();
        
        setTimeout(() => {
            if (faq.q === "Saldo Top Up belum masuk?") {
                messages.value.push({ sender: 'bot', text: 'Jika sudah lebih dari 2 menit saldo belum bertambah, silakan isi form di bawah ini agar Admin bisa langsung mengeceknya:' });
                botState.value = 'SALDO_FORM';
            } else {
                messages.value.push({ sender: 'bot', text: 'Apakah jawaban ini membantu bosku?' });
                botState.value = 'ASK_FEEDBACK';
            }
            scrollToBottom();
        }, 1000);
    }, 600);
};

// 2. SAAT BOT NANYA "MEMBANTU ATAU TIDAK?"
const handleFeedback = (isHelpful) => {
    messages.value.push({ sender: 'user', text: isHelpful ? 'Ya, sangat membantu' : 'Tidak membantu' });
    botState.value = 'TYPING';
    scrollToBottom();

    setTimeout(() => {
        if (isHelpful) {
            messages.value.push({ sender: 'bot', text: 'Mantap bos! Ada pertanyaan lain yang mau ditanyakan?' });
            botState.value = 'MAIN_MENU';
        } else {
            messages.value.push({ sender: 'bot', text: 'Waduh, maaf ya bos. FlashBot masih belajar. Mau disambungkan ngobrol langsung dengan admin?' });
            botState.value = 'ASK_ADMIN';
        }
        scrollToBottom();
    }, 600);
};

// 3. SAAT BOT NANYA "MAU HUBUNGI ADMIN?"
const handleAdminPrompt = async (wantsAdmin) => {
    messages.value.push({ sender: 'user', text: wantsAdmin ? 'Boleh, hubungkan ke admin' : 'Tidak usah' });
    botState.value = 'TYPING';
    scrollToBottom();

    if (wantsAdmin) {
        try {
            const res = await axios.post('/chat/request');
            chatSessionId.value = res.data.session_id;
            telegramStatus.value = 'waiting';
            
            setTimeout(() => {
                messages.value.push({ sender: 'bot', text: 'Sistem sedang memanggil Admin... Mohon tunggu sebentar ya bos.' });
                botState.value = 'CHAT_TELEGRAM'; 
                scrollToBottom();
                startChatPolling(); 
            }, 600);

        } catch (error) {
            console.error("Gagal request chat", error);
            setTimeout(() => {
                messages.value.push({ sender: 'bot', text: 'Maaf bos, server sedang sibuk. Silakan coba lagi nanti.' });
                botState.value = 'MAIN_MENU';
                scrollToBottom();
            }, 600);
        }

    } else {
        setTimeout(() => {
            messages.value.push({ sender: 'bot', text: 'Oke bos! FlashBot *standby* di sini kalau butuh bantuan lagi ya. Ada yang mau ditanya lagi?' });
            botState.value = 'MAIN_MENU';
            scrollToBottom();
        }, 600);
    }
};

const submitSaldoForm = async () => {
    if (!saldoForm.value.nama || !saldoForm.value.email || !saldoForm.value.no_invoice) return;

    const { nama, email, no_invoice } = saldoForm.value;

    messages.value.push({ sender: 'user', text: `Mengirim laporan saldo...<br>Nama: ${nama}<br>Email: ${email}<br>No Inv: ${no_invoice}` });
    botState.value = 'TYPING';
    scrollToBottom();

    try {
        // NEMBAK LANGSUNG KE API FORM (Tanpa bikin sesi chat baru)
        await axios.post('/chat/report-saldo', {
            nama: nama,
            email: email,
            no_invoice: no_invoice
        });

        setTimeout(() => {
            // Tampilkan konfirmasi form ke user
            messages.value.push({ 
                sender: 'bot', 
                text: `Laporan berhasil dikirim ke Telegram Admin dan akan segera diproses! 🚀<br><br><b>Detail Form:</b><br>Nama: ${nama}<br>Email: ${email}<br>No. Invoice: ${no_invoice}<br><br>Mohon ditunggu pengecekannya ya bosku.` 
            });
            
            // 🌟 KUNCI UTAMANYA DI SINI: Balik ke Main Menu, BUKAN ke Chat Telegram
            botState.value = 'MAIN_MENU'; 
            scrollToBottom();
            
            // Reset form
            saldoForm.value = { nama: '', email: '', no_invoice: '' };
        }, 800);

    } catch (error) {
        console.error("Gagal kirim laporan", error);
        setTimeout(() => {
            messages.value.push({ sender: 'bot', text: 'Maaf bos, server sedang sibuk (Error 404/500). Laporan gagal dikirim, pastikan Route sudah benar.' });
            botState.value = 'MAIN_MENU';
            scrollToBottom();
        }, 600);
    }
};

// ==========================================
// 🌟 FITUR CHAT TELEGRAM (LIVE CHAT)
// ==========================================
const sendWebMessage = async () => {
    if (!inputMessage.value.trim() || telegramStatus.value === 'closed') return;
    
    messages.value.push({ sender: 'user', text: inputMessage.value });
    const msgToSend = inputMessage.value;
    inputMessage.value = '';
    scrollToBottom();
    
    try {
        await axios.post('/chat/send', {
            session_id: chatSessionId.value,
            message: msgToSend
        });
    } catch (error) {
        console.error("Gagal kirim pesan", error);
    }
};

const fetchChatUpdates = async () => {
    if (!chatSessionId.value || botState.value !== 'CHAT_TELEGRAM') return;
    
    try {
        const res = await axios.get(`/chat/pull/${chatSessionId.value}`);
        
        if (telegramStatus.value === 'waiting' && res.data.status === 'active') {
            messages.value.push({ sender: 'bot', text: '✅ Admin telah terhubung! Silakan ketik pertanyaan Anda di bawah.' });
            scrollToBottom();
        }

        telegramStatus.value = res.data.status;
        
        if (res.data.messages && res.data.messages.length > 0) {
            res.data.messages.forEach(dbMsg => {
                if (dbMsg.sender_type === 'admin') {
                    const isExist = messages.value.some(m => m.db_id === dbMsg.id);
                    if (!isExist) {
                        messages.value.push({ sender: 'admin', text: dbMsg.message, db_id: dbMsg.id });
                        scrollToBottom();
                    }
                }
            });
        }

        if (telegramStatus.value === 'closed') {
            clearInterval(chatPollInterval);
            messages.value.push({ sender: 'bot', text: '🛑 Sesi obrolan telah diakhiri oleh Admin. Terima kasih!' });
            setTimeout(() => {
                botState.value = 'MAIN_MENU'; 
                chatSessionId.value = null;
                scrollToBottom();
            }, 3000);
        }
    } catch (error) {
        console.error("Gagal menarik pesan", error);
    }
};

const startChatPolling = () => {
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(fetchChatUpdates, 3000); 
};

const closeBot = () => {
    isOpen.value = false;
    if (chatPollInterval && telegramStatus.value !== 'active') {
        clearInterval(chatPollInterval);
        botState.value = 'MAIN_MENU';
        chatSessionId.value = null;
    }
};

onUnmounted(() => {
    if (chatPollInterval) clearInterval(chatPollInterval);
});
</script>

<template>
    <div class="fixed bottom-40 right-6 z-50 font-sans">
        
        
        <button v-if="!isOpen" @click="isOpen = true; scrollToBottom()" class="bg-blue-600 hover:bg-blue-500 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-[0_0_15px_rgba(37,99,235,0.5)] hover:scale-110 transition transform animate-bounce">
            
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
        </button>

        <div v-else class="bg-slate-800 border border-slate-700 w-[350px] max-w-[90vw] rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up origin-bottom-right">
            
            <div class="bg-slate-900 p-4 flex justify-between items-center border-b border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white shadow-inner">
                        <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="2" x2="12" y2="5"/>
                            <circle cx="12" cy="2" r="1" fill="currentColor"/>
                            <rect x="5" y="6" width="14" height="11" rx="3"/>
                            <circle cx="9" cy="11" r="1.2" fill="currentColor"/>
                            <circle cx="15" cy="11" r="1.2" fill="currentColor"/>
                            <line x1="10" y1="15" x2="14" y2="15"/>
                        </svg>
                    </div>
                    
                    <div>
                        <h3 class="text-white font-black text-sm tracking-widest uppercase">FlashBot</h3>
                        <p class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span> 
                            {{ botState === 'CHAT_TELEGRAM' ? 'Tersambung ke Admin' : 'Online' }}
                        </p>
                    </div>
                </div>
                <button @click="closeBot" class="text-slate-400 hover:text-white p-2 bg-slate-800 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Area Chat List -->
            <div ref="chatContainer" class="p-4 h-[420px] overflow-y-auto flex flex-col gap-3 bg-slate-800/50 scroll-smooth">
                
                <div v-for="(msg, idx) in messages" :key="idx" 
                     :class="msg.sender === 'user' ? 'items-end' : 'items-start'" class="flex flex-col w-full animate-fade-in">
                    
                    <div :class="msg.sender === 'user' ? 'bg-blue-600 text-white rounded-tl-xl rounded-b-xl' : (msg.sender === 'admin' ? 'bg-emerald-600 text-white rounded-tr-xl rounded-b-xl' : 'bg-slate-700 text-slate-200 rounded-tr-xl rounded-b-xl')" 
                         class="p-3 max-w-[85%] text-sm shadow-sm leading-relaxed" v-html="msg.text">
                    </div>
                    <span class="text-[9px] text-slate-500 font-bold mt-1 uppercase">
                        {{ msg.sender === 'user' ? 'Kamu' : (msg.sender === 'admin' ? 'Admin Asli' : 'FlashBot') }}
                    </span>
                </div>

                <div v-if="botState === 'TYPING' || (botState === 'CHAT_TELEGRAM' && telegramStatus === 'waiting')" class="flex flex-col w-full items-start animate-fade-in mt-auto">
                    <div class="bg-slate-700 p-3 rounded-tr-xl rounded-b-xl flex items-center gap-1 w-fit h-10 shadow-sm">
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                    </div>
                    <span class="text-[9px] text-slate-500 font-bold mt-1 uppercase">
                        {{ botState === 'CHAT_TELEGRAM' ? 'Menunggu Admin...' : 'FlashBot mengetik...' }}
                    </span>
                </div>

                <!-- Area Menu / Input -->
                <div v-if="botState !== 'TYPING'" class="flex flex-col gap-2 mt-auto animate-fade-in pt-4">
                    
                    <template v-if="botState === 'MAIN_MENU'">
                        <button v-for="(faq, i) in faqs" :key="i" @click="askQuestion(faq)" 
                                class="text-left text-xs font-bold bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/30 p-3 rounded-xl transition">
                            {{ faq.q }}
                        </button>
                    </template>

                    <template v-else-if="botState === 'ASK_FEEDBACK'">
                        <div class="flex gap-2">
                            <button @click="handleFeedback(true)" class="flex-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 p-2 rounded-xl text-xs font-bold transition">Ya, Membantu</button>
                            <button @click="handleFeedback(false)" class="flex-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 p-2 rounded-xl text-xs font-bold transition">Tidak</button>
                        </div>
                    </template>

                    <template v-else-if="botState === 'ASK_ADMIN'">
                        <div class="flex gap-2">
                            <button @click="handleAdminPrompt(true)" class="flex-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 p-2 rounded-xl text-xs font-bold transition">Ya, Hubungi Admin</button>
                            <button @click="handleAdminPrompt(false)" class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-300 border border-slate-600 p-2 rounded-xl text-xs font-bold transition">Tidak Usah</button>
                        </div>
                    </template>

                    <!-- 🌟 AREA FORM LAPORAN SALDO (NAMA, EMAIL, NO INVOICE) -->
                    <template v-else-if="botState === 'SALDO_FORM'">
                        <div class="flex flex-col gap-2 bg-slate-800/80 p-3 rounded-xl border border-slate-700 mt-2">
                            <input v-model="saldoForm.nama" type="text" placeholder="Nama di Akun" class="bg-slate-900 border border-slate-600 text-xs text-white rounded-lg p-2.5 focus:outline-none focus:border-blue-500 placeholder-slate-500">
                            <input v-model="saldoForm.email" type="email" placeholder="Email Akun" class="bg-slate-900 border border-slate-600 text-xs text-white rounded-lg p-2.5 focus:outline-none focus:border-blue-500 placeholder-slate-500">
                            <input v-model="saldoForm.no_invoice" type="text" placeholder="No. Invoice (Contoh: INV-123)" class="bg-slate-900 border border-slate-600 text-xs text-white rounded-lg p-2.5 focus:outline-none focus:border-blue-500 placeholder-slate-500">
                            <div class="flex gap-2 mt-1">
                                <button @click="submitSaldoForm" :disabled="!saldoForm.nama || !saldoForm.email || !saldoForm.no_invoice" class="flex-1 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-700 disabled:text-slate-500 text-white p-2.5 rounded-lg text-xs font-bold transition shadow-md">Kirim Laporan</button>
                                <button @click="botState = 'MAIN_MENU'" class="bg-slate-700 hover:bg-slate-600 text-slate-300 p-2.5 rounded-lg text-xs font-bold transition border border-slate-600">Batal</button>
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <!-- 🌟 FITUR CHAT TELEGRAM: INPUT MESSAGE -->
            <div v-if="botState === 'CHAT_TELEGRAM'" class="bg-slate-900 p-3 border-t border-slate-700 flex gap-2">
                <input 
                    v-model="inputMessage" 
                    @keyup.enter="sendWebMessage"
                    type="text" 
                    placeholder="Ketik pesan..." 
                    class="flex-1 bg-slate-800 border border-slate-700 text-sm text-white rounded-xl px-4 focus:outline-none focus:border-blue-500 placeholder-slate-500"
                    :disabled="telegramStatus === 'closed'"
                >
                <button 
                    @click="sendWebMessage"
                    :disabled="!inputMessage.trim() || telegramStatus === 'closed'"
                    class="bg-blue-600 hover:bg-blue-500 disabled:bg-slate-700 disabled:text-slate-500 text-white w-10 h-10 rounded-xl flex items-center justify-center transition shadow-md shrink-0">
                    <svg class="w-4 h-4 transform rotate-90 translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
@keyframes slide-up { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fade-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>