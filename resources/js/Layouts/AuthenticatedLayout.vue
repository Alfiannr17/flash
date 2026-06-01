<template>

    <div v-if="showAnnouncement && announcement" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 animate-fade-in">
        <div class="bg-slate-900 border border-slate-700/80 px-6 md:px-8 py-8 rounded-3xl shadow-[0_30px_60px_rgba(0,0,0,0.8)] flex flex-col items-center gap-6 min-w-[280px] max-w-sm w-full">
            <div class="w-16 h-16 rounded-full bg-blue-500/10 flex items-center justify-center border border-blue-500/20 shadow-[0_0_20px_rgba(59,130,246,0.2)]">
                <span class="text-3xl drop-shadow-lg">📢</span>
            </div>
            
            <div class="text-center w-full">
                <h3 class="text-lg md:text-xl font-black text-white uppercase tracking-widest mb-3">{{ announcement.title }}</h3>
                <div class="max-h-48 overflow-y-auto custom-scrollbar-dark px-2">
                    <p class="text-slate-300 text-xs md:text-sm font-medium leading-relaxed whitespace-pre-wrap">{{ announcement.message }}</p>
                </div>
            </div>
            
            <button @click="closeAnnouncement" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-xl uppercase tracking-widest text-xs transition-all active:scale-95 shadow-[0_0_15px_rgba(37,99,235,0.3)] mt-2">
                OK Mengerti
            </button>
        </div>
    </div>

    <div class="min-h-screen bg-slate-900 font-sans pb-24 relative">
        
        <div v-if="isLoading" class="fixed inset-0 z-[9999] bg-slate-900/80 backdrop-blur-md flex flex-col items-center justify-center">
            <div class="relative w-20 h-20 flex items-center justify-center">
                <div class="absolute inset-0 border-4 border-slate-700 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>
                <svg class="w-8 h-8 text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <p class="mt-5 text-xs font-black text-blue-400 uppercase tracking-[0.3em] animate-pulse">Memuat...</p>
        </div>
        <header class="bg-slate-800/90 backdrop-blur-lg sticky top-0 z-40 p-4 border-b border-slate-700 flex justify-between items-center px-4 md:px-8 shadow-sm">
            
            <div class="flex items-center gap-3">
                <div class="bg-blue-600 p-2 rounded-xl shadow-lg shadow-blue-900/50">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="font-black text-lg text-white uppercase tracking-tighter">Flash Otp</h1>
            </div>
            
            <div v-if="$page.props.auth?.user" class="flex items-center gap-3">
                <div class="bg-slate-900 px-3 py-1 rounded-xl flex items-center gap-2 border border-slate-700 shadow-inner">
                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
                    <span class="text-md font-black text-blue-400 tracking-tighter uppercase">
                        Rp {{ Number($page.props.auth?.user?.balance || 0).toLocaleString('id-ID') }}
                    </span>
                </div>
                
                <Link :href="safeRoute('logout')" method="post" as="button" class="p-2 bg-slate-900 border border-slate-700 rounded-xl text-rose-500 hover:bg-rose-500/10 hover:border-rose-500/50 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </Link>
            </div>
            <div v-else class="text-xs font-bold text-slate-500">GUEST MODE</div>
        </header>

        <main class="w-full flex-1 p-4 md:p-8">
            <slot />
        </main>

        <nav class="fixed bottom-0 left-0 w-full bg-slate-800 border-t border-slate-700 z-50 shadow-[0_-10px_40px_rgba(0,0,0,0.3)] pb-safe rounded-t-3xl md:rounded-none">
            <div class="flex justify-around items-center h-20 max-w-2xl mx-auto px-2">
                
                <template v-for="item in menu" :key="item.name">
                    <Link :href="safeRoute(item.routeName)" 
                        class="relative flex flex-col items-center justify-center w-full h-full transition-all duration-300 ease-in-out group"
                        :class="$page.component === item.component ? 'text-blue-500' : 'text-slate-500 hover:text-slate-300'">
                        
                        <div v-if="$page.component === item.component" class="absolute top-0 w-8 h-1 bg-blue-500 rounded-b-full shadow-[0_0_10px_rgba(59,130,246,0.8)]"></div>
                        
                        <div class="mt-1 transition-transform duration-300" :class="$page.component === item.component ? '-translate-y-1 scale-110' : 'group-hover:scale-110'">
                            <svg v-if="item.icon === 'home'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            <svg v-if="item.icon === 'wallet'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <svg v-if="item.icon === 'cart'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <svg v-if="item.icon === 'tag'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            <svg v-if="item.icon === 'gift'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                            <svg v-if="item.icon === 'user'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        
                        <span class="text-[10px] font-black mt-1.5 uppercase tracking-widest" :class="$page.component === item.component ? 'opacity-100' : 'opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all'">
                            {{ item.name }}
                        </span>
                    </Link>
                </template>

            </div>
        </nav>

        
        <FlashBot />
        <a href="https://t.me/posttaxk" target="_blank" rel="noopener noreferrer" 
           class="fixed z-[90] bottom-[80px] right-6 w-14 h-14 bg-sky-500 hover:bg-sky-400 text-white rounded-full shadow-[0_0_20px_rgba(14,165,233,0.6)] transition-all duration-300 hover:scale-110 flex items-center justify-center group cursor-pointer border-2 border-sky-400/50 animate-[bounce_1s_infinite_1.5s]">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.98 1.25-5.58 3.63-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.06-.48-.83-.26-1.49-.4-1.43-.84.03-.23.36-.47 1-.72 3.91-1.7 6.52-2.82 7.82-3.36 3.73-1.55 4.5-1.81 5.01-1.82.11 0 .35.03.48.14.11.09.14.22.15.34-.01.07-.01.19-.02.32z"/></svg>
            <span class="absolute right-full mr-3 bg-slate-800/5 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-slate-700">Grup Telegram</span>
        </a>

    </div>
</template>

<script setup>
import FlashBot from '@/Components/FlashBot.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

// Tarik data pengumuman dari Middleware
const page = usePage();
const announcement = page.props.flash_announcement;
const showAnnouncement = ref(false);

onMounted(() => {
    if (announcement) {
        // Cek di memori browser, apakah user udah pernah nutup pengumuman INI?
        // Pakai ID pengumuman+updated_at biar kalau lu ngedit text, pop-up nya muncul lagi
        const cacheKey = 'popup_closed_' + announcement.id + '_' + new Date(announcement.updated_at).getTime();
        const isClosed = localStorage.getItem(cacheKey);

        if (!isClosed) {
            // Kasih delay dikit (0.5 detik) biar animasinya smooth pas user baru masuk
            setTimeout(() => {
                showAnnouncement.value = true;
            }, 500);
        }
    }
});

const closeAnnouncement = () => {
    showAnnouncement.value = false;
    const cacheKey = 'popup_closed_' + announcement.id + '_' + new Date(announcement.updated_at).getTime();
    localStorage.setItem(cacheKey, 'true'); // Simpan di browser biar ga muncul lagi
};

// State untuk ngontrol muncul/tidaknya loading overlay
const isLoading = ref(false);

let removeStartEventListener;
let removeFinishEventListener;

// ==========================================
// 🌟 FITUR BARU: LOGIKA CHAT TELEGRAM
// ==========================================
const chatSessionId = ref(null);
const chatStatus = ref(''); // 'waiting', 'active', 'closed'
const messages = ref([]);
const inputMessage = ref('');
let chatPollInterval = null;

const connectToAdmin = async () => {
    try {
        const res = await axios.post('/chat/request');
        chatSessionId.value = res.data.session_id;
        chatStatus.value = 'waiting';
        startChatPolling();
    } catch (error) {
        console.error("Gagal request chat", error);
    }
};

const sendWebMessage = async () => {
    if (!inputMessage.value || chatStatus.value === 'closed') return;
    
    try {
        await axios.post('/chat/send', {
            session_id: chatSessionId.value,
            message: inputMessage.value
        });
        inputMessage.value = '';
        fetchChatUpdates(); // Segarkan layar setelah kirim
    } catch (error) {
        console.error("Gagal kirim pesan", error);
    }
};

const fetchChatUpdates = async () => {
    if (!chatSessionId.value) return;
    try {
        const res = await axios.get(`/chat/pull/${chatSessionId.value}`);
        chatStatus.value = res.data.status;
        messages.value = res.data.messages;

        if (chatStatus.value === 'closed') {
            clearInterval(chatPollInterval);
            alert('Sesi obrolan telah diakhiri oleh Admin.');
        }
    } catch (error) {
        console.error("Gagal menarik pesan", error);
    }
};

const startChatPolling = () => {
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(fetchChatUpdates, 3000); // Ngecek balasan admin tiap 3 detik
};
// ==========================================

onMounted(() => {
    // Nyalain loading saat mulai pindah halaman
    removeStartEventListener = router.on('start', () => { 
        isLoading.value = true; 
    });
    
    // Matiin loading pas halaman selesai dimuat (atau kalau error)
    removeFinishEventListener = router.on('finish', () => { 
        isLoading.value = false; 
    });
});

onUnmounted(() => {
    // Bersihin event listener biar gak bocor memory
    if (removeStartEventListener) removeStartEventListener();
    if (removeFinishEventListener) removeFinishEventListener();
    
    // 🌟 Bersihin radar polling chat saat user keluar
    if (chatPollInterval) clearInterval(chatPollInterval);
});

const safeRoute = (name) => {
    try {
        if (typeof route !== 'undefined' && route().has(name)) {
            return route(name);
        }
    } catch (e) {}
    return '#';
};

const menu = [
    { name: 'Home', routeName: 'dashboard', component: 'Dashboard', icon: 'home' },
    { name: 'Topup', routeName: 'topup.create', component: 'TopUp/Create', icon: 'wallet' },
    { name: 'Order', routeName: 'order.create', component: 'Order/Create', icon: 'cart' },
    { name: 'Referral', routeName: 'referral.index', component: 'Referral/Index', icon: 'gift' }, // 🌟 MENU BARU
    { name: 'Profil', routeName: 'profile.edit', component: 'Profile/Edit', icon: 'user' },
];
</script>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>