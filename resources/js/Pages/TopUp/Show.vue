<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    topUp: Object
});

const page = usePage();

const timeLeft = ref(0);
let timer = null;
let pollTimeout = null; 

const showCancelModal = ref(false);

// Fitur Copy
const copied = ref(false);
const copyInvoice = (text) => {
    navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

// --- LOGIKA PERHITUNGAN YANG BENAR (ANTI TEKOR) ---
// Biaya Pajak = (Nominal * 0.7%) + 310
const biayaPajak = computed(() => {
    const amount = Number(props.topUp.amount) || 0;
    return Math.floor((amount * 0.007) + 310);
});

// Total Bayar = Nominal Asli + Biaya Pajak
const totalBayar = computed(() => {
    const amount = Number(props.topUp.amount) || 0;
    return amount + biayaPajak.value;
});

// Format Waktu
const formattedDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { 
        year: 'numeric', month: 'numeric', day: 'numeric', 
        hour: '2-digit', minute:'2-digit' 
    });
};

const calculateTimeLeft = () => {
    const createdAt = new Date(props.topUp.created_at).getTime();
    const now = new Date().getTime();
    const expiresAt = createdAt + 120000; 
    const diff = Math.floor((expiresAt - now) / 1000);
    timeLeft.value = diff > 0 ? diff : 0;
};

const pollStatus = () => {
    if (props.topUp.status !== 'pending' || timeLeft.value <= 0) return;

    axios.get(window.location.href, {
        headers: { 
            'X-Inertia': 'true',
            'X-Inertia-Version': page.version
        }
    })
    .then(response => {
        const currentStatus = response.data.props.topUp.status;
        
        if (currentStatus === 'success') {
            clearInterval(timer);
            clearTimeout(pollTimeout);
            router.reload(); 
        } else {
            pollTimeout = setTimeout(pollStatus, 3000);
        }
    })
    .catch(error => {
        if (error.response && error.response.status === 409) {
            window.location.reload();
        } else {
            pollTimeout = setTimeout(pollStatus, 5000);
        }
    });
};

onMounted(() => {
    if (props.topUp.status === 'pending') {
        calculateTimeLeft();
        
        timer = setInterval(() => {
            if (timeLeft.value > 0) {
                timeLeft.value--;
            } else {
                clearInterval(timer);
                clearTimeout(pollTimeout);
            }
        }, 1000);

        pollTimeout = setTimeout(pollStatus, 3000);
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
    if (pollTimeout) clearTimeout(pollTimeout);
});

const formattedTime = computed(() => {
    const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0');
    const s = (timeLeft.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const executeCancel = () => {
    showCancelModal.value = false;
    router.visit(route('topup.create'));
};



// --- FITUR LOADING QRIS ---
const isQrisLoading = ref(true);

const onQrisLoad = () => {
    isQrisLoading.value = false;
};
</script>

<template>
    <Head title="Detail Pembayaran" />

    <AuthenticatedLayout>
        <div class="relative space-y-6 animate-fade-in pb-12 min-h-screen text-slate-200">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-blue-600/10 rounded-full blur-[100px] md:blur-[120px] pointer-events-none"></div>

            <div class="w-full  mx-auto relative z-10  sm:px-0">
                
                <div class="bg-slate-800/80 backdrop-blur-xl p-6 md:p-10 rounded-3xl border border-slate-700/60 relative overflow-hidden">
                    
                    <template v-if="topUp.status === 'pending' && timeLeft > 0">
                        
                        <div class="text-center mb-8">
                            <div class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-5 py-2.5 rounded-full text-[10px] md:text-xs font-black uppercase tracking-widest mb-6 inline-flex items-center gap-2 shadow-[0_0_15px_rgba(59,130,246,0.2)] animate-pulse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                BATAS WAKTU: {{ formattedTime }}
                            </div>
                            
                            <h3 class="text-xs md:text-sm text-slate-400 mb-1 font-black uppercase tracking-widest">Total Pembayaran </h3>
                            <div class="font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400 text-5xl md:text-6xl tracking-tighter drop-shadow-lg">
                                Rp {{ totalBayar.toLocaleString('id-ID') }}
                            </div>
                        </div>

                        <div class="flex justify-center mb-8">
                            <div class="p-4 md:p-5 bg-white rounded-3xl shadow-[0_0_50px_rgba(255,255,255,0.1)] border-4 border-slate-700 inline-block transform transition hover:scale-[1.02] duration-300 relative min-w-[250px] min-h-[250px] flex items-center justify-center overflow-hidden">
                                
                                <div v-if="isQrisLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10 rounded-2xl">
                                    <svg class="w-10 h-10 text-blue-500 animate-spin mb-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest animate-pulse">Memuat QRIS...</span>
                                </div>

                                <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' + encodeURIComponent(topUp.checkout_url)" 
                                     alt="Scan QRIS" 
                                     class="w-56 h-56 md:w-64 md:h-64 object-contain rounded-xl transition-opacity duration-500"
                                     :class="isQrisLoading ? 'opacity-0' : 'opacity-100'"
                                     @load="onQrisLoad">
                            </div>
                        </div>

                        <div class="bg-slate-900/60 rounded-3xl border border-slate-700/80 p-6 md:p-8 mb-8 shadow-inner">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                                <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Dibuat Pada</span>
                                <span class="text-xs md:text-sm font-black text-white">{{ formattedDate(topUp.created_at) }} WIB</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Nominal Top Up</span>
                                    <span class="text-xs md:text-sm font-black text-white">Rp {{ Number(topUp.amount).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Pajak PPN & Layanan</span>
                                    <span class="text-xs md:text-sm font-black text-white">+ Rp {{ biayaPajak.toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="flex justify-between items-center bg-blue-500/5 p-3 rounded-xl border border-blue-500/20 mt-2">
                                    <div class="text-left">
                                        <p class="text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-0.5">Order ID / Invoice</p>
                                        <p class="text-sm md:text-base font-black text-white tracking-wider">{{ topUp.reference }}</p>
                                    </div>
                                    <button @click="copyInvoice(topUp.reference)" class="flex items-center gap-2 bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 px-3 py-2 rounded-lg transition-colors border border-blue-500/30">
                                        <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        <svg v-else class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8 text-left">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2 px-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Cara Pembayaran
                            </h4>
                            <div class="grid grid-cols-1 gap-3">
                                <div class="flex items-start gap-4 bg-slate-900/40 p-4 rounded-2xl border border-slate-700/50">
                                    <span class="bg-slate-800 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 border border-slate-600">1</span>
                                    <p class="text-xs text-slate-300 leading-relaxed">Buka aplikasi <b>Dana, OVO, GoPay, LinkAja</b> atau <b>M-Banking</b> Anda.</p>
                                </div>
                                <div class="flex items-start gap-4 bg-slate-900/40 p-4 rounded-2xl border border-slate-700/50">
                                    <span class="bg-slate-800 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 border border-slate-600">2</span>
                                    <p class="text-xs text-slate-300 leading-relaxed">Scan kode QR di atas atau simpan ke galeri untuk diunggah di aplikasi pembayaran.</p>
                                </div>
                                <div class="flex items-start gap-4 bg-slate-900/40 p-4 rounded-2xl border border-slate-700/50">
                                    <span class="bg-slate-800 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 border border-slate-600">3</span>
                                    <p class="text-xs text-slate-300 leading-relaxed">Pastikan membayar <b>Rp {{ totalBayar.toLocaleString('id-ID') }}</b>. Saldo akan otomatis masuk hitungan detik.</p>
                                </div>
                            </div>
                        </div>

                        <button @click="router.reload({ only: ['topUp'] })" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black py-4.5 rounded-2xl transition-all shadow-[0_10px_25px_rgba(37,99,235,0.3)] hover:shadow-[0_15px_35px_rgba(37,99,235,0.4)] tracking-[0.1em] text-xs md:text-sm uppercase active:scale-[0.98] flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Konfirmasi Pembayaran
                        </button>
                    </template>

                    <template v-else-if="topUp.status === 'success'">
                        <div class="py-4 md:py-6 animate-slide-up text-center">
                            <div class="w-24 h-24 bg-emerald-500/10 text-emerald-400 border-2 border-emerald-500/30 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_40px_rgba(16,185,129,0.4)]">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="font-black text-3xl md:text-5xl text-white mb-2 uppercase tracking-tighter drop-shadow-md">TOP UP SUKSES!</h3>
                            <p class="text-sm text-slate-400 mb-10 font-medium">Pembayaran diterima, saldo telah ditambahkan ke akun Anda.</p>
                            
                            <div class="bg-slate-900/60 backdrop-blur-sm border-2 border-dashed border-slate-700/70 rounded-[2rem] p-6 md:p-10 mb-10 text-left relative overflow-hidden shadow-2xl">
                                
                                

                                <div class="space-y-6">
                                    

                                    <div class="grid grid-cols-1 gap-4 text-xs md:text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 pt-2 font-bold uppercase tracking-widest">Order ID</span>
                                            <div class="flex items-center gap-1.5">
                                                <button @click="copyInvoice(topUp.reference)" class="text-blue-400 hover:text-white transition-colors">
                                                    <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    <svg v-else class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                                <span class="text-slate-300 font-black uppercase">{{ topUp.reference }}</span>
                                                
                                            </div>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Dibuat Pada</span>
                                            <span class="text-slate-300 font-medium">{{ formattedDate(topUp.created_at) }} WIB</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Selesai Pada</span>
                                            <span class="text-slate-300 font-medium">{{ formattedDate(topUp.updated_at) }} WIB</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Metode Pembayaran</span>
                                            <span class="text-blue-400 font-black uppercase tracking-widest">QRIS</span>
                                        </div>
                                    </div>

                                    <div class="pt-6 mt-2 border-t-2 border-dashed border-slate-700/70">
                                        <div class="flex justify-between items-center pb-2">
                                        <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Nominal Top Up</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl md:text-2xl font-black text-emerald-400">Rp {{ Number(topUp.amount).toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>
                                        <div class="flex justify-between items-center mb-6">
                                            <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Pajak PPN & Layanan</span>
                                            <span class="text-xs md:text-sm font-bold text-slate-400">+ Rp {{ biayaPajak.toLocaleString('id-ID') }}</span>
                                        </div>
                                        <div class="pt-6 mt-2 border-t-2 border-dashed border-slate-700/70 flex justify-between items-center ">
                                            <span class="text-xs md:text-sm font-black text-white uppercase tracking-[0.2em]">Total Dibayar</span>
                                            <span class="text-lg md:text-xl font-black text-white">Rp {{ totalBayar.toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button @click="router.visit(route('dashboard'))" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black py-4.5 rounded-2xl transition-all shadow-[0_10px_25px_rgba(37,99,235,0.3)] hover:shadow-[0_15px_35px_rgba(37,99,235,0.4)] tracking-[0.2em] text-xs md:text-sm uppercase active:scale-[0.98]">
                                Selesai & Kembali
                            </button>
                        </div>
                    </template>

                    <template v-else-if="topUp.status === 'canceled' || (topUp.status === 'pending' && timeLeft <= 0)">
                        <div class="animate-slide-up text-center">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-rose-500/10 text-rose-400 border-2 border-rose-500/30 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_40px_rgba(225,29,72,0.3)]">
                                <svg class="w-10 h-10 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <h3 class="font-black text-3xl md:text-4xl text-white mb-2 uppercase tracking-tighter drop-shadow-md">
                                {{ topUp.status === 'canceled' ? 'TOP UP DIBATALKAN!' : 'TOP UP EXPIRED!' }}
                            </h3>
                            <p class="text-sm text-slate-400 mb-10 font-medium">Tagihan ini sudah tidak berlaku.</p>
                            
                            <div class="bg-slate-900/60 backdrop-blur-sm border-2 border-dashed border-rose-900/50 rounded-[2rem] p-6 md:p-10 mb-10 text-left relative overflow-hidden shadow-2xl">
                                
                                <div class="absolute -left-5 top-[52%] -translate-y-1/2 w-10 h-10 bg-slate-800 rounded-full border-r-2 border-rose-900/50 hidden md:block"></div>
                                <div class="absolute -right-5 top-[52%] -translate-y-1/2 w-10 h-10 bg-slate-800 rounded-full border-l-2 border-rose-900/50 hidden md:block"></div>

                                <div class="space-y-6 opacity-80">
                                    

                                    <div class="grid grid-cols-1 gap-4 text-xs md:text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Order ID</span>
                                            <div class="flex items-center gap-1.5">
                                                <button @click="copyInvoice(topUp.reference)" class="p-1.5 text-slate-400 hover:text-white rounded-lg transition-colors">
                                                    <svg v-if="!copied" class="w-4 h-4 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    <svg v-else class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                                <span class="text-slate-300 font-black uppercase">{{ topUp.reference }}</span>
                                                
                                            </div>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Dibuat Pada</span>
                                            <span class="text-slate-300 font-medium">{{ formattedDate(topUp.created_at) }} WIB</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">{{ topUp.status === 'canceled' ? 'Dibatalkan Pada' : 'Expired Pada' }}</span>
                                            <span class="text-rose-400 font-bold">
                                                {{ topUp.status === 'canceled' ? formattedDate(topUp.updated_at) : formattedDate(new Date(topUp.created_at).getTime() + 120000) }} WIB
                                            </span>
                                        </div>
                                    </div>

                                    <div class="pt-6 mt-2 border-t-2 border-dashed border-slate-700/70">
                                        <div class="flex justify-between items-center border-slate-700/50 pb-1">
                                        <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Nominal Top Up</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl md:text-2xl font-black text-slate-500 line-through">Rp {{ Number(topUp.amount).toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>

                                        <div class="flex justify-between items-center mb-6">

                                            <span class="text-[10px] md:text-xs font-bold text-slate-500 uppercase tracking-widest">Pajak PPN & Layanan</span>
                                            <span class="text-xs md:text-sm font-bold text-slate-500 line-through">+ Rp {{ biayaPajak.toLocaleString('id-ID') }}</span>
                                        </div>
                                        <div class="flex justify-between items-center pt-6 mt-2 border-t-2 border-dashed border-slate-700/70">
                                            <span class="text-xs md:text-sm font-black text-rose-500 uppercase tracking-[0.2em]">Total</span>
                                            <span class="text-2xl md:text-3xl font-black text-rose-500/60 line-through decoration-rose-500/80">Rp {{ totalBayar.toLocaleString('id-ID') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button @click="router.visit(route('topup.create'))" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-black py-4.5 rounded-2xl transition tracking-widest text-xs md:text-sm uppercase shadow-lg active:scale-[0.98] block mb-4">
                                Buat Tagihan Baru
                            </button>
                            <button @click="router.visit(route('dashboard'))" class="w-full bg-transparent pt-3 text-slate-400 hover:text-white font-bold  rounded-2xl transition tracking-widest text-xs md:text-sm uppercase">
                                Kembali ke Dashboard
                            </button>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        <div v-if="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-800 border border-slate-700 w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden p-8 text-center relative border-t-4 border-t-rose-500">
                <div class="mx-auto w-16 h-16 bg-rose-500/10 border-2 border-rose-500/30 rounded-full flex items-center justify-center mb-5 shadow-[0_0_30px_rgba(225,29,72,0.2)]">
                    <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-xl font-black text-white uppercase tracking-tighter mb-2">Batalkan Tagihan?</h3>
                <p class="text-slate-400 text-sm font-medium leading-relaxed mb-8">Anda yakin ingin membatalkan tagihan ini? QRIS tidak akan dapat digunakan lagi.</p>
                <div class="flex flex-col gap-3">
                    <button @click="executeCancel" class="w-full bg-rose-600 hover:bg-rose-500 text-white font-black py-4 rounded-xl transition-all shadow-lg shadow-rose-900/50 uppercase tracking-widest text-xs active:scale-95">Ya, Batalkan</button>
                    <button @click="showCancelModal = false" class="w-full bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-700 font-bold py-4 rounded-xl transition uppercase tracking-widest text-xs">Kembali</button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fade-in { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
.py-4\.5 { padding-top: 1.125rem; padding-bottom: 1.125rem; }
</style>