<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    recentOrders: Array,
    recentTopUps: Array,
    totalOrderCount: Number, 
    totalTopUpCount: Number,
    banners: Array, 
    liveNotifs: Array 
});

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Link Referral berhasil disalin! Gas sebarkan bos!'); 
};

const currentSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
    if (props.banners && props.banners.length > 0) {
        currentSlide.value = (currentSlide.value + 1) % props.banners.length;
    }
};

const prevSlide = () => {
    if (props.banners && props.banners.length > 0) {
        currentSlide.value = currentSlide.value === 0 ? props.banners.length - 1 : currentSlide.value - 1;
    }
};

const goToSlide = (index) => {
    currentSlide.value = index;
};

const startSlider = () => {
    if (props.banners && props.banners.length > 1) {
        slideInterval = setInterval(nextSlide, 4000); // Geser otomatis tiap 4 detik
    }
};

const stopSlider = () => {
    if (slideInterval) clearInterval(slideInterval);
};

// ==========================================
// 🌟 LOGIKA LIVE NOTIFICATION FOMO
// ==========================================
const currentNotif = ref(null);
const showNotif = ref(false);
let notifIndex = 0;
let notifInterval = null;

const playLiveNotifications = () => {
    if (!props.liveNotifs || props.liveNotifs.length === 0) return;

    // Putar notifikasi setiap 6 detik
    notifInterval = setInterval(() => {
        currentNotif.value = props.liveNotifs[notifIndex];
        showNotif.value = true;

        // Sembunyikan notifikasi setelah 4 detik
        setTimeout(() => {
            showNotif.value = false;
        }, 4000);

        notifIndex = (notifIndex + 1) % props.liveNotifs.length; // Looping terus
    }, 6000); 
};

onMounted(() => {
    startSlider();
    setTimeout(playLiveNotifications, 2000); // Jalankan FOMO setelah 2 detik masuk web
});

onUnmounted(() => {
    stopSlider();
    if (notifInterval) clearInterval(notifInterval);
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <transition name="slide-fade">
            <div v-if="showNotif && currentNotif" class="fixed bottom-[90px]  md:left-2 z-[50] bg-slate-900/90 backdrop-blur-md border border-slate-700 p-3 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.5)] flex items-center gap-3 w-[55 %] max-w-[280px] pointer-events-none">

                <div class="flex-1 min-w-0">
                    <p class="text-[9px] md:text-[10px] text-white font-black leading-snug truncate whitespace-normal line-clamp-2">{{ currentNotif.message }}</p>
                    <p class="text-[8px] text-slate-400 font-bold  mt-0.5">{{ currentNotif.time }}</p>
                </div>
            </div>
        </transition>
        <div class="relative space-y-6 animate-fade-in pb-10 min-h-screen text-slate-200">
            
            <div v-if="banners && banners.length > 0" 
                 class="relative w-full h-40 md:h-64 lg:h-80 mt-4 rounded-3xl overflow-hidden shadow-[0_10px_40px_rgba(0,0,0,0.5)] group border border-slate-700/50"
                 @mouseenter="stopSlider" 
                 @mouseleave="startSlider"
                 @touchstart="stopSlider"
                 @touchend="startSlider">
                
                <div class="flex transition-transform duration-700 ease-in-out h-full" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
                    <div v-for="(banner, index) in banners" :key="banner.id" class="w-full h-full shrink-0 relative bg-slate-900 flex items-center justify-center">
                        
                        <div class="absolute inset-0 flex flex-col items-center justify-center z-0 bg-slate-800">
                            <div class="w-8 h-8 border-4 border-slate-600 border-t-blue-500 rounded-full animate-spin mb-3"></div>
                            <span class="text-[9px] md:text-[10px] font-black text-slate-500 uppercase tracking-widest animate-pulse">Memuat Banner...</span>
                        </div>

                        <img :src="banner.image_path.startsWith('/') ? banner.image_path : '/' + banner.image_path" 
                             :alt="banner.title" 
                             loading="eager" 
                             fetchpriority="high" 
                             class="w-full h-full object-cover relative z-10 bg-slate-900/0" />
                             
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none z-20"></div>
                    </div>
                </div>

                <button v-if="banners.length > 1" @click="prevSlide" class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 w-7 h-7 md:w-8 md:h-8 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all backdrop-blur-md border border-white/20 shadow-md active:scale-95 z-30">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button v-if="banners.length > 1" @click="nextSlide" class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 w-7 h-7 md:w-8 md:h-8 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all backdrop-blur-md border border-white/20 shadow-md active:scale-95 z-30">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <div v-if="banners.length > 1" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-30 bg-black/20 px-2.5 py-1 rounded-full backdrop-blur-md border border-white/10">
                    <button v-for="(_, index) in banners" :key="index" @click="goToSlide(index)"
                        :class="['w-1.5 h-1.5 rounded-full transition-all duration-300', currentSlide === index ? 'bg-blue-400 w-4 shadow-[0_0_10px_rgba(59,130,246,0.8)]' : 'bg-white/50 hover:bg-white']">
                    </button>
                </div>
            </div>

            <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 mt-4 rounded-3xl border border-slate-700/80 shadow-2xl flex items-center justify-between group hover:border-blue-500/30 transition-colors">
                <div class="text-left">
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tighter mb-1 md:mb-2 capitalize">
                        Halo, {{ $page.props.auth?.user?.name || 'Guest' }}!
                    </h1>
                    <p class="text-xs md:text-sm text-slate-400 font-medium">
                        Selamat datang kembali, pantau statistik transaksimu di sini.
                    </p>
                </div>

                <div class="flex flex-col items-center shrink-0 ml-4">
                    <Link 
                        :href="route('order.create')" 
                        class="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center bg-blue-600 rounded-2xl shadow-[0_0_20px_rgba(37,99,235,0.4)] hover:bg-blue-500 transition-all hover:scale-110 active:scale-95 border border-blue-400/50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </Link>
                    <span class="text-[9px] md:text-[10px] font-black uppercase tracking-widest mt-2 text-slate-400 group-hover:text-blue-400 transition-colors">
                        Order Baru
                    </span>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 md:p-8 rounded-3xl border border-blue-400/50 shadow-[0_0_40px_rgba(37,99,235,0.3)] mb-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg class="w-40 h-40 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                </div>
                
                <h3 class="text-white font-black text-lg md:text-xl tracking-widest uppercase mb-2 relative z-10 flex items-center gap-2">
                    <span class="text-2xl">🎁</span> Program Referral
                </h3>
                <p class="text-blue-100 text-xs md:text-sm mb-6 font-medium relative z-10 max-w-2xl leading-relaxed">
                    Sebarkan link di bawah ini. Dapatkan komisi s/d. <span class="bg-white/20 px-2 py-0.5 rounded-md text-white font-black border border-white/30 shadow-sm">10% Saldo</span> SETIAP KALI teman kamu melakukan Top Up!
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3 relative z-10">
                    <div class="flex-1 bg-slate-950/50 border border-blue-400/30 px-4 py-3 md:py-4 rounded-xl text-xs md:text-sm font-mono text-blue-200 overflow-hidden text-ellipsis whitespace-nowrap shadow-inner">
                        {{ route('register') }}?ref={{ $page.props.auth.user.referral_code }}
                    </div>
                    <button @click="copyToClipboard(route('register') + '?ref=' + $page.props.auth.user.referral_code)" class="bg-white hover:bg-slate-100 text-blue-600 px-6 py-3 md:py-4 rounded-xl transition-all font-black text-xs md:text-sm shadow-lg whitespace-nowrap active:scale-95 uppercase tracking-widest">
                        COPY LINK
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-slate-800 to-slate-900 p-6 md:p-8 rounded-3xl shadow-2xl border border-slate-700 flex items-center justify-between relative overflow-hidden group">
                    <div class="absolute inset-0 bg-blue-600 opacity-0 group-hover:opacity-5 transition-opacity duration-500"></div>
                    <div class="relative z-10">
                        <p class="font-black text-[10px] md:text-xs text-slate-400 uppercase tracking-widest mb-1">Saldo utama</p>
                        <h3 class="text-3xl md:text-4xl font-black text-white tracking-tighter drop-shadow-md">
                            Rp {{ Number($page.props.auth?.user?.balance || 0).toLocaleString('id-ID') }}
                        </h3>
                    </div>

                    <div class="flex flex-col items-center shrink-0 relative z-10">
                        <Link 
                            :href="route('topup.create')" 
                            class="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center bg-emerald-500 rounded-2xl shadow-[0_0_20px_rgba(16,185,129,0.3)] hover:bg-emerald-400 transition-all hover:scale-110 active:scale-95 border border-emerald-400/50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                            </svg>
                        </Link>
                        <span class="text-[9px] md:text-[10px] font-black uppercase tracking-widest mt-2 text-slate-400 group-hover:text-emerald-400 transition-colors">
                            Isi Saldo
                        </span>
                    </div>
                </div>
                
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-xl flex flex-col justify-between">
                    <p class="font-black text-[10px] md:text-xs text-slate-400 uppercase tracking-widest">Total Order</p>
                    <h3 class="text-4xl md:text-5xl font-black text-white tracking-tighter my-3">{{ totalOrderCount || 0 }}</h3>
                    <span class="text-[9px] md:text-[10px] font-black text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 px-3 py-1.5 rounded-lg uppercase w-fit tracking-widest">Berjalan Lancar</span>
                </div>

                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-xl flex flex-col justify-between">
                    <p class="font-black text-[10px] md:text-xs text-slate-400 uppercase tracking-widest">Total Top Up</p>
                    <h3 class="text-4xl md:text-5xl font-black text-white tracking-tighter my-3">{{ totalTopUpCount || 0 }}</h3>
                    <span class="text-[9px] md:text-[10px] font-black text-blue-400 bg-blue-400/10 border border-blue-400/20 px-3 py-1.5 rounded-lg uppercase w-fit tracking-widest">Transaksi Sukses</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-xl flex flex-col h-[400px]">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-700/50 pb-4">
                        <h3 class="font-black text-white tracking-tight uppercase text-sm md:text-base flex items-center gap-2">
                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span> Riwayat Order
                        </h3>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-slate-900 px-2 py-1 rounded-md">Terbaru</span>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar-dark">
                        <template v-if="recentOrders && recentOrders.length > 0">
                            <Link 
                                v-for="order in recentOrders" 
                                :key="order.id" 
                                :href="route('order.waiting', order.id)"
                                class="flex justify-between items-center p-4 bg-slate-900/40 rounded-2xl border border-slate-700/50 hover:border-blue-500/50 hover:bg-slate-800 transition cursor-pointer w-full text-left group"
                            >
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-black text-sm text-white uppercase">{{ order.service_code }} </p>
                                        <p v-if="order.status === 'completed' && order.otp_code" class="text-[10px] font-black text-emerald-400 tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                                            OTP: {{ order.otp_code }}
                                        </p>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-500">{{ new Date(order.created_at).toLocaleDateString('id-ID') }} </p>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="font-black text-sm text-blue-400 group-hover:text-blue-300 transition-colors">Rp {{ Number(order.price).toLocaleString('id-ID') }}</p>
                                    <span :class="{'text-emerald-400': order.status === 'completed', 'text-amber-400': order.status === 'waiting_otp', 'text-rose-400': order.status === 'canceled'}" class="text-[9px] font-black uppercase tracking-widest block mt-1">
                                        {{ order.status.replace('_', ' ') }}
                                    </span>
                                </div>
                            </Link>
                        </template>
                        <div v-else class="h-full flex flex-col items-center justify-center py-10 text-center opacity-50">
                            <svg class="w-10 h-10 text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Belum ada riwayat</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-xl flex flex-col h-[400px]">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-700/50 pb-4">
                        <h3 class="font-black text-white tracking-tight uppercase text-sm md:text-base flex items-center gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Riwayat Top Up
                        </h3>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-slate-900 px-2 py-1 rounded-md">Terbaru</span>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar-dark">
                        <template v-if="recentTopUps && recentTopUps.length > 0">
                            <Link v-for="topup in recentTopUps" :key="topup.id" :href="route('topup.show', topup.reference)" class="flex justify-between items-center p-4 bg-slate-900/40 rounded-2xl border border-slate-700/50 hover:border-emerald-500/30 hover:bg-slate-800 transition cursor-pointer w-full text-left group">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-black text-xs md:text-sm text-white uppercase">{{ topup.reference }}</p>
                                        <p class="text-[9px] md:text-[10px] font-bold text-slate-500 mt-1">{{ new Date(topup.created_at).toLocaleDateString('id-ID') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-xs md:text-sm text-emerald-400">+Rp {{ Number(topup.amount).toLocaleString('id-ID') }}</p>
                                    <span :class="{'text-emerald-400': topup.status === 'success', 'text-amber-400': topup.status === 'pending', 'text-rose-400': topup.status === 'failed'}" class="text-[9px] font-black uppercase tracking-widest block mt-1">
                                        {{ topup.status }}
                                    </span>
                                </div>
                            </Link>
                        </template>
                        <div v-else class="h-full flex flex-col items-center justify-center py-10 text-center opacity-50">
                            <svg class="w-10 h-10 text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Belum ada riwayat</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.5s ease-out forwards; }

/* 🌟 EFEK MUNCUL NOTIFIKASI FOMO DARI ATAS (Lebih Elegan) */
.slide-fade-enter-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-enter-from { transform: translateY(-20px); opacity: 0; }
.slide-fade-leave-to { transform: translateY(-20px); opacity: 0; }

/* Scrollbar khusus tema gelap */
.custom-scrollbar-dark::-webkit-scrollbar { width: 4px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>