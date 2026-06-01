<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    totalInvited: Number,
    historyKomisi: Array,
    historyWd: Array
});

const page = usePage();
const reffBalance = computed(() => Number(page.props.auth?.user?.reff_balance || 0));

// State untuk mengatur Tab Riwayat (Komisi / Withdraw)
const activeTab = ref('komisi');

// ==========================================
// 🌟 CUSTOM TOAST NOTIFICATION (TOMBOL OK)
// ==========================================
const notification = ref({ show: false, message: '' });

const showToast = (message) => {
    notification.value = { show: true, message };
};

const closeToast = () => {
    notification.value.show = false;
};

// ==========================================
// FORM TUKAR KE SALDO UTAMA
// ==========================================
const convertForm = useForm({ amount: '' });
const setMaxConvert = () => {
    if (reffBalance.value > 0) convertForm.amount = reffBalance.value;
};
const submitConvert = () => {
    convertForm.post(route('referral.convert'), {
        preserveScroll: true,
        onSuccess: () => { convertForm.reset(); showToast('Berhasil Tukar ke Saldo Utama!'); }
    });
};

// ==========================================
// FORM WITHDRAW KE DANA
// ==========================================
const wdForm = useForm({ amount: '', dana_number: '' });
const wdOptions = [10000, 20000, 50000, 100000];

const selectWdAmount = (amt) => { wdForm.amount = amt; };
const submitWd = () => {
    wdForm.post(route('referral.withdraw'), {
        preserveScroll: true,
        onSuccess: () => { wdForm.reset(); showToast('Permintaan Penarikan Berhasil Dikirim! Proses pencairan dana memerlukan waktu maksimal 1×24 jam.'); }
    });
};

// ==========================================
// FITUR COPY LINK (CUSTOM BUTTON TEXT)
// ==========================================
const copyText = ref('Salin Link');
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    copyText.value = 'Berhasil Disalin!';
    
    setTimeout(() => {
        copyText.value = 'Salin Link';
    }, 2000);
};

// Format tanggal untuk history
const formatDate = (dateString) => {
    const options = { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// Mengambil error array jadi string biasa
const getError = (errorObj) => {
    if (Array.isArray(errorObj)) return errorObj[0];
    return errorObj;
};
</script>

<template>
    <Head title="Program Referral - Flash OTP" />

    <transition name="toast-center">
        <div v-if="notification.show" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-700/80 px-5 py-5 rounded-3xl flex flex-col items-center gap-4 w-[88%] max-w-[340px]">
                <div class="mt-4 mb-2 w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center border border-blue-500/20 shadow-[0_0_20px_rgba(59,130,246,0.2)]">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <span class="text-white text-sm md:text-base tracking-wider text-center leading-relaxed">{{ notification.message }}</span>
                
                <button @click="closeToast" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-xl uppercase tracking-widest text-xs transition-all active:scale-95 shadow-[0_0_15px_rgba(37,99,235,0.3)] mt-2">
                    OK Mengerti
                </button>
            </div>
        </div>
    </transition>

    <AuthenticatedLayout>
        <div class="relative space-y-6 animate-fade-in pb-10 min-h-screen text-slate-200">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] bg-indigo-600/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-4xl mx-auto space-y-6 relative z-10 mt-4 md:mt-8 lg:px-0">
                
                <div class="bg-slate-800/60  p-6 md:p-8 rounded-3xl md:rounded-[2rem] border border-slate-700/80 shadow-2xl relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px] pointer-events-none group-hover:bg-indigo-500/20 transition-colors duration-700"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start gap-8 mb-8 border-b border-slate-700/50 pb-8 relative z-10">
                        

                        <div class="flex flex-col gap-4 w-full md:w-1/3 shrink-0">
                            <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/80 text-center shadow-inner">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mb-2">Saldo Referral</p>
                                <h3 class="text-3xl font-black text-white tracking-tighter">
                                    Rp {{ reffBalance.toLocaleString('id-ID') }}
                                </h3>
                            </div>
                            
                            <div class="bg-slate-800/50 border border-slate-700/50 px-5 py-4 rounded-2xl flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Teman Diundang</span>
                                <span class="text-white font-black text-sm">{{ totalInvited }} Orang</span>
                            </div>
                        </div>
                        <div class="text-center md:text-left w-full md:w-2/3">
                            <h2 class="text-2xl md:text-3xl font-black text-white mb-2 tracking-tight">Program Referral</h2>
                            <p class="text-slate-400 text-xs md:text-sm font-medium">
                                Undang teman dan dapatkan komisi s/d. 10% dari setiap transaksi yang mereka lakukan.
                            </p>
                            
                            <div class="mt-6 flex flex-col sm:flex-row gap-3 items-center">
                                <div class="w-full bg-slate-900/80 border border-slate-700 px-5 py-4 rounded-2xl text-xs md:text-sm font-mono text-slate-300 overflow-hidden whitespace-nowrap text-ellipsis shadow-inner">
                                    {{ route('register') }}?ref={{ page.props.auth.user.referral_code }}
                                </div>
                                <button @click="copyToClipboard(route('register') + '?ref=' + page.props.auth.user.referral_code)" 
                                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white font-black px-8 py-4 rounded-2xl uppercase tracking-widest text-xs transition-all active:scale-95 shadow-[0_0_20px_rgba(37,99,235,0.3)]">
                                    {{ copyText }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10 mb-8">
                        
                        <div class="bg-slate-900/40 p-6 rounded-3xl border border-slate-700/50 shadow-inner flex flex-col h-full">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center border border-emerald-500/30 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-white font-black text-sm md:text-base tracking-wide">Tukar ke Saldo Utama</h4>
                                    <p class="text-[10px] md:text-xs text-slate-400 font-medium">Tanpa Minimal Penukaran.</p>
                                </div>
                            </div>
                            
                            <form @submit.prevent="submitConvert" class="space-y-4 mt-auto">
                                <div>
                                    <div class="flex gap-2">
                                        <input v-model="convertForm.amount" type="number" placeholder="Ketik nominal..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors outline-none">
                                        <button @click="setMaxConvert" type="button" class="bg-slate-700 hover:bg-slate-600 border border-slate-600 text-white font-black px-4 rounded-xl text-[10px] tracking-widest uppercase transition-all">MAX</button>
                                    </div>
                                    <p v-if="convertForm.errors.reff_amount" class="text-rose-400 text-[10px] font-bold mt-2">{{ getError(convertForm.errors.reff_amount) }}</p>
                                </div>

                                <button type="submit" :disabled="convertForm.processing || !convertForm.amount" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(16,185,129,0.2)] hover:shadow-[0_0_25px_rgba(16,185,129,0.4)] disabled:opacity-50 disabled:shadow-none transition-all active:scale-95">
                                    {{ convertForm.processing ? 'Memproses...' : 'Tukar Sekarang' }}
                                </button>
                            </form>
                        </div>

                        <div class="bg-slate-900/40 p-6 rounded-3xl border border-slate-700/50 shadow-inner flex flex-col h-full">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-500/20 text-blue-400 rounded-full flex items-center justify-center border border-blue-500/30 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-white font-black text-sm md:text-base tracking-wide">Tarik Tunai (DANA)</h4>
                                    <p class="text-[10px] md:text-xs text-slate-400 font-medium">Ke e-Wallet. <span class="text-rose-400 font-bold">Fee: Rp 1.000</span></p>
                                </div>
                            </div>
                            
                            <form @submit.prevent="submitWd" class="space-y-4 mt-auto">
                                <div>
                                    <div class="grid grid-cols-4 gap-2 mb-3">
                                        <button v-for="amt in wdOptions" :key="amt" type="button" @click="selectWdAmount(amt)"
                                            :class="['py-2.5 rounded-xl font-black transition-all border text-xs', 
                                            wdForm.amount === amt ? 'border-blue-500 bg-blue-500/20 text-blue-400 shadow-inner' : 'border-slate-700/80 text-slate-400 hover:border-slate-500 hover:bg-slate-800 bg-slate-900/50']">
                                            {{ amt / 1000 }}K
                                        </button>
                                    </div>
                                    
                                    <input v-model="wdForm.dana_number" type="text" inputmode="numeric" pattern="[0-9]*" placeholder="Nomor DANA (08...)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors outline-none">
                                    
                                    <div v-if="wdForm.errors.wd_amount || wdForm.errors.dana_number" class="text-rose-400 text-[10px] font-bold mt-2">
                                        <p v-if="wdForm.errors.wd_amount">{{ getError(wdForm.errors.wd_amount) }}</p>
                                        <p v-if="wdForm.errors.dana_number">{{ getError(wdForm.errors.dana_number) }}</p>
                                    </div>
                                </div>

                                <button type="submit" :disabled="wdForm.processing || !wdForm.amount || !wdForm.dana_number" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(37,99,235,0.2)] hover:shadow-[0_0_25px_rgba(37,99,235,0.4)] disabled:opacity-50 disabled:shadow-none transition-all active:scale-95">
                                    {{ wdForm.processing ? 'Memproses...' : 'Tarik Rp ' + (wdForm.amount ? Number(wdForm.amount).toLocaleString('id-ID') : '0') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="relative z-10 border-t border-slate-700/50 pt-8">
                        
                        <div class="flex justify-center border-b border-slate-700 mb-6 gap-6 overflow-x-auto custom-scrollbar-dark pb-1">
                            <button @click="activeTab = 'komisi'" :class="['pb-3 text-xs md:text-sm font-black uppercase tracking-widest whitespace-nowrap transition-all border-b-2', activeTab === 'komisi' ? 'text-indigo-400 border-indigo-400' : 'text-slate-500 border-transparent hover:text-slate-300']">
                                Riwayat Saldo
                            </button>
                            <button @click="activeTab = 'wd'" :class="['pb-3 text-xs md:text-sm font-black uppercase tracking-widest whitespace-nowrap transition-all border-b-2', activeTab === 'wd' ? 'text-blue-400 border-blue-400' : 'text-slate-500 border-transparent hover:text-slate-300']">
                                Status Penarikan
                            </button>
                        </div>

                        <div v-show="activeTab === 'komisi'" class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar-dark animate-fade-in">
                            <template v-if="historyKomisi && historyKomisi.length > 0">
                                <div v-for="(item, index) in historyKomisi" :key="index" class="flex justify-between items-center p-5 bg-slate-900/50 rounded-2xl border border-slate-700/60 hover:bg-slate-800 transition-all duration-300">
                                    <div class="flex items-center gap-3 md:gap-4">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                             :class="item.type === 'komisi' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'">
                                            <svg v-if="item.type === 'komisi'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-black text-sm text-white uppercase tracking-tight">{{ item.type === 'komisi' ? 'Komisi Referral' : 'Tukar ke Saldo Utama' }}</p>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">{{ formatDate(item.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p :class="item.type === 'komisi' ? 'text-emerald-400' : 'text-amber-400'" class="font-black text-sm">
                                            {{ item.type === 'komisi' ? '+' : '-' }} Rp {{ Number(item.amount).toLocaleString('id-ID') }}
                                        </p>
                                      
                                    </div>
                                </div>
                            </template>
                            <div v-else class="py-10 text-center opacity-50">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Belum ada komisi masuk</p>
                            </div>
                        </div>

                        <div v-show="activeTab === 'wd'" class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar-dark animate-fade-in">
                            <template v-if="historyWd && historyWd.length > 0">
                                <div v-for="(item, index) in historyWd" :key="index" 
                                     class="p-5 bg-slate-900/50 rounded-2xl border border-slate-700/60 hover:bg-slate-800 transition-all duration-300">
                                    
                                    <div class=" justify-between items-start gap-3 md:gap-4 flex">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                            </div>
                                            <div class="space-y-1">
                                                <p class="font-black text-sm text-white uppercase tracking-tight">Tarik Tunai</p>
                                                <p class="text-[10px] font-bold text-slate-500 uppercase">{{ formatDate(item.created_at) }}</p>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <span :class="{'text-emerald-400 ': item.status === 'success', 'text-amber-400 ': item.status === 'pending', 'text-rose-400 ': item.status === 'failed'}" 
                                                  class="text-[9px] font-black uppercase  inline-block">
                                                {{ item.status === 'pending' ? 'Diproses' : item.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-4 pt-3 border-t border-slate-700/50">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-500 uppercase mb-1">Invoice</p>
                                            <p class="text-[11px] font-mono text-slate-300">{{ item.reference }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[9px] font-bold text-slate-500 uppercase mb-1">Tujuan DANA</p>
                                            <p class="text-[11px] font-bold text-blue-400">{{ item.destination_number }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-slate-700/50 flex justify-between items-center">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase">Total Penarikan</p>
                                        <p class=" font-black text-sm">
                                             Rp {{ Number(item.amount).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                            
                            <div v-else class="py-10 text-center opacity-50">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Belum ada penarikan</p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }

/* 🌟 STYLE UNTUK TOAST NOTIFICATION TENGAH */
.toast-center-enter-active, .toast-center-leave-active { transition: all 0.3s ease-out; }
.toast-center-enter-from, .toast-center-leave-to { opacity: 0; transform: scale(0.95); }
.toast-center-enter-to, .toast-center-leave-from { opacity: 1; transform: scale(1); }

.custom-scrollbar-dark::-webkit-scrollbar { width: 5px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.5); border-radius: 10px; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb:hover { background: #475569; }

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type="number"] {
    -moz-appearance: textfield;
}
</style>